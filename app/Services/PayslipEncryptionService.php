<?php

namespace App\Services;

use App\Models\Payslip;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use mikehaertl\pdftk\Pdf;

class PayslipEncryptionService
{
    /**
     * Ensure a payslip PDF is encrypted. Updates encryption_status when successful.
     */
    public function ensureEncrypted(Payslip $payslip, User $employee): bool
    {
        if ((int) $payslip->encryption_status === Payslip::STATUS_SUCCESSFUL) {
            return true;
        }

        if (empty($employee->pdf_password)) {
            $payslip->update([
                'encryption_status' => Payslip::STATUS_FAILED,
                'failure_reason' => __('payslips.encryption_error') . ': Missing PDF password',
            ]);

            return false;
        }

        if (empty($payslip->file) || !Storage::disk('modified')->exists($payslip->file)) {
            $payslip->update([
                'encryption_status' => Payslip::STATUS_FAILED,
                'failure_reason' => __('payslips.payslip_file_not_found'),
            ]);

            return false;
        }

        $absolutePath = Storage::disk('modified')->path($payslip->file);

        if (isPdfEncrypted($absolutePath)) {
            $payslip->update([
                'encryption_status' => Payslip::STATUS_SUCCESSFUL,
                'failure_reason' => null,
            ]);

            return true;
        }

        $tempFile = $payslip->file . '.enc_tmp_' . uniqid();

        try {
            $pdf = new Pdf($absolutePath, [
                'command' => config('ciblerh.pdftk_path'),
            ]);

            $saved = $pdf->setUserPassword($employee->pdf_password)
                ->passwordEncryption(128)
                ->saveAs(Storage::disk('modified')->path($tempFile));

            if (!$saved || !Storage::disk('modified')->exists($tempFile)) {
                throw new \RuntimeException('Failed generating encrypted file');
            }

            Storage::disk('modified')->delete($payslip->file);
            Storage::disk('modified')->move($tempFile, $payslip->file);

            $payslip->update([
                'encryption_status' => Payslip::STATUS_SUCCESSFUL,
                'failure_reason' => null,
            ]);

            return true;
        } catch (\Throwable $e) {
            if (Storage::disk('modified')->exists($tempFile)) {
                Storage::disk('modified')->delete($tempFile);
            }

            Log::error('On-demand payslip encryption failed', [
                'payslip_id' => $payslip->id,
                'employee_id' => $employee->id,
                'file' => $payslip->file,
                'error' => $e->getMessage(),
            ]);

            $payslip->update([
                'encryption_status' => Payslip::STATUS_FAILED,
                'failure_reason' => __('payslips.encryption_error') . ': ' . $e->getMessage(),
            ]);

            return false;
        }
    }
}
