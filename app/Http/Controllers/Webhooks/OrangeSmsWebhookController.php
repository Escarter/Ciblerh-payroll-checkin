<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Payslip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Receives Orange Cameroun (Business Messaging Ngage) SMS delivery-report (DR) callbacks and updates the
 * delivery status of the payslip that originated each SMS.
 *
 * The MODOP spec does not document the DR payload shape, so parsing is defensive: we accept a single report
 * or a list of them, and we look for the transaction id (txnId / clientTxnId) and a status field under the
 * common spellings Orange platforms use.
 */
class OrangeSmsWebhookController extends Controller
{
    public function deliveryReport(Request $request)
    {
        if (! $this->tokenIsValid($request)) {
            Log::warning('Orange SMS DR webhook rejected: invalid or missing token', [
                'ip' => $request->ip(),
            ]);

            return response()->json(['status' => 'unauthorized'], 401);
        }

        $payload = $request->json()->all();
        if (! is_array($payload) || $payload === []) {
            $payload = $request->all();
        }

        $reports = $this->extractReports($payload);
        if ($reports === []) {
            Log::info('Orange SMS DR webhook: no recognizable reports in payload', [
                'top_level_keys' => is_array($payload) ? array_slice(array_keys($payload), 0, 12) : [],
            ]);

            return response()->json(['status' => 'ok', 'processed' => 0]);
        }

        $processed = 0;
        foreach ($reports as $report) {
            if (! is_array($report)) {
                continue;
            }
            if ($this->processReport($report)) {
                $processed++;
            }
        }

        return response()->json(['status' => 'ok', 'processed' => $processed]);
    }

    /**
     * Verify the shared secret when one is configured. When no token is set, the endpoint stays open
     * (consistent with the email webhooks), but configuring ORANGE_CM_DR_CALLBACK_TOKEN is recommended.
     */
    protected function tokenIsValid(Request $request): bool
    {
        $expected = trim((string) config('services.orange_cm.dr_callback_token', ''));
        if ($expected === '') {
            return true;
        }

        $provided = (string) ($request->query('token')
            ?? $request->header('X-Webhook-Token')
            ?? $request->input('token', ''));

        return hash_equals($expected, trim($provided));
    }

    /**
     * Normalize the payload into a flat list of report rows.
     */
    protected function extractReports(array $payload): array
    {
        foreach (['reports', 'deliveryReports', 'data', 'list', 'results', 'items'] as $key) {
            if (isset($payload[$key]) && is_array($payload[$key])) {
                return array_is_list($payload[$key]) ? $payload[$key] : [$payload[$key]];
            }
        }

        // A bare list of reports, or a single report object.
        return array_is_list($payload) ? $payload : [$payload];
    }

    protected function processReport(array $report): bool
    {
        $txnId = $this->firstValue($report, ['txnId', 'txnID', 'txnid', 'transactionId', 'messageId', 'msgId', 'id']);
        $clientTxnId = $this->firstValue($report, ['clientTxnId', 'clientTxnID', 'clientTxnid', 'clientTransactionId']);
        $rawStatus = $this->firstValue($report, ['deliveryStatus', 'dlrStatus', 'status', 'statusMsg', 'state', 'result']);

        if ($txnId === null && $clientTxnId === null) {
            Log::info('Orange SMS DR webhook: report had no usable transaction id', [
                'report_keys' => array_slice(array_keys($report), 0, 12),
            ]);

            return false;
        }

        $payslip = $this->findPayslip($txnId, $clientTxnId);
        if (! $payslip) {
            Log::warning('Orange SMS DR webhook: no payslip matched the delivery report', [
                'txn_id' => $txnId,
                'client_txn_id' => $clientTxnId,
                'status' => $rawStatus,
            ]);

            return false;
        }

        $status = $this->mapStatus((string) $rawStatus);

        $update = [
            'sms_delivery_status' => $status,
            'sms_delivery_note' => 'Orange DR: '.(string) $rawStatus,
        ];

        if ($status === Payslip::SMS_DELIVERY_STATUS_DELIVERED) {
            $update['sms_delivered_at'] = now();
        } elseif (in_array($status, [Payslip::SMS_DELIVERY_STATUS_FAILED, Payslip::SMS_DELIVERY_STATUS_EXPIRED, Payslip::SMS_DELIVERY_STATUS_REJECTED], true)) {
            // A definitive non-delivery overrides the optimistic "accepted by Orange" send status.
            $update['sms_sent_status'] = Payslip::STATUS_FAILED;
        }

        $payslip->update($update);

        Log::info('Orange SMS DR webhook: updated payslip delivery status', [
            'payslip_id' => $payslip->id,
            'delivery_status' => $status,
            'raw_status' => $rawStatus,
        ]);

        return true;
    }

    protected function findPayslip(?string $txnId, ?string $clientTxnId): ?Payslip
    {
        $query = Payslip::query();

        if ($txnId !== null && $clientTxnId !== null) {
            $query->where(function ($q) use ($txnId, $clientTxnId) {
                $q->where('sms_txn_id', $txnId)->orWhere('sms_client_txn_id', $clientTxnId);
            });
        } elseif ($txnId !== null) {
            $query->where('sms_txn_id', $txnId);
        } else {
            $query->where('sms_client_txn_id', $clientTxnId);
        }

        return $query->latest('sms_sent_at')->first();
    }

    /**
     * Map a provider status string to one of our SMS delivery constants. Handles common SMPP/DLR spellings
     * (DELIVRD, UNDELIV, EXPIRED, REJECTD, ...) as well as plain English.
     */
    protected function mapStatus(string $raw): string
    {
        $s = strtolower(trim($raw));

        if ($s === '') {
            return Payslip::SMS_DELIVERY_STATUS_PENDING;
        }
        if (str_contains($s, 'expir')) {
            return Payslip::SMS_DELIVERY_STATUS_EXPIRED;
        }
        if (str_contains($s, 'reject') || str_contains($s, 'rejectd') || str_contains($s, 'senderid') || str_contains($s, 'blacklist') || str_contains($s, 'not_match')) {
            return Payslip::SMS_DELIVERY_STATUS_REJECTED;
        }
        if (str_contains($s, 'deliv') && ! str_contains($s, 'undeliv')) {
            return Payslip::SMS_DELIVERY_STATUS_DELIVERED;
        }
        if (str_contains($s, 'success') || $s === 'ok' || $s === 'dlvrd') {
            return Payslip::SMS_DELIVERY_STATUS_DELIVERED;
        }
        if (str_contains($s, 'undeliv') || str_contains($s, 'fail') || str_contains($s, 'error') || str_contains($s, 'unknown')) {
            return Payslip::SMS_DELIVERY_STATUS_FAILED;
        }

        // Enroute / accepted / buffered, etc. — still in flight.
        return Payslip::SMS_DELIVERY_STATUS_PENDING;
    }

    protected function firstValue(array $data, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $data) && $data[$key] !== null && $data[$key] !== '') {
                return (string) $data[$key];
            }
        }

        return null;
    }
}
