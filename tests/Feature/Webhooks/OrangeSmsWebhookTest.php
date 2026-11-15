<?php

use App\Models\Payslip;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;

uses(RefreshDatabase::class);

function makePayslipForDr(array $overrides = []): Payslip
{
    return Payslip::factory()->create(array_merge([
        'sms_sent_status' => Payslip::STATUS_SUCCESSFUL,
        'sms_txn_id' => 'txn-123',
        'sms_client_txn_id' => 'client-123',
        'sms_delivery_status' => Payslip::SMS_DELIVERY_STATUS_PENDING,
        'sms_sent_at' => now(),
    ], $overrides));
}

beforeEach(function () {
    Config::set('services.orange_cm.dr_callback_token', '');
    // UserObserver assigns the "employee" role on user creation (via the Payslip factory graph).
    \Spatie\Permission\Models\Role::findOrCreate('employee', 'web');
});

test('delivery report marks payslip delivered by txnId', function () {
    $payslip = makePayslipForDr();

    $this->postJson('/webhooks/orange-sms/dr', [
        'txnId' => 'txn-123',
        'deliveryStatus' => 'DELIVRD',
    ])->assertOk()->assertJson(['status' => 'ok', 'processed' => 1]);

    $payslip->refresh();
    expect($payslip->sms_delivery_status)->toBe(Payslip::SMS_DELIVERY_STATUS_DELIVERED);
    expect($payslip->sms_delivered_at)->not->toBeNull();
    expect($payslip->sms_sent_status)->toBe(Payslip::STATUS_SUCCESSFUL);
});

test('delivery report marks payslip failed and flips sent status', function () {
    $payslip = makePayslipForDr();

    $this->postJson('/webhooks/orange-sms/dr', [
        'clientTxnId' => 'client-123',
        'status' => 'UNDELIV',
    ])->assertOk();

    $payslip->refresh();
    expect($payslip->sms_delivery_status)->toBe(Payslip::SMS_DELIVERY_STATUS_FAILED);
    expect($payslip->sms_sent_status)->toBe(Payslip::STATUS_FAILED);
});

test('delivery report accepts a list of reports', function () {
    $a = makePayslipForDr(['sms_txn_id' => 'txn-a', 'sms_client_txn_id' => 'client-a']);
    $b = makePayslipForDr(['sms_txn_id' => 'txn-b', 'sms_client_txn_id' => 'client-b']);

    $this->postJson('/webhooks/orange-sms/dr', [
        'reports' => [
            ['txnId' => 'txn-a', 'deliveryStatus' => 'DELIVRD'],
            ['txnId' => 'txn-b', 'deliveryStatus' => 'EXPIRED'],
        ],
    ])->assertOk()->assertJson(['processed' => 2]);

    expect($a->fresh()->sms_delivery_status)->toBe(Payslip::SMS_DELIVERY_STATUS_DELIVERED);
    expect($b->fresh()->sms_delivery_status)->toBe(Payslip::SMS_DELIVERY_STATUS_EXPIRED);
    expect($b->fresh()->sms_sent_status)->toBe(Payslip::STATUS_FAILED);
});

test('delivery report rejects bad token when one is configured', function () {
    Config::set('services.orange_cm.dr_callback_token', 'sekret');
    $payslip = makePayslipForDr();

    $this->postJson('/webhooks/orange-sms/dr?token=wrong', [
        'txnId' => 'txn-123',
        'deliveryStatus' => 'DELIVRD',
    ])->assertStatus(401);

    expect($payslip->fresh()->sms_delivery_status)->toBe(Payslip::SMS_DELIVERY_STATUS_PENDING);
});

test('delivery report accepts valid token', function () {
    Config::set('services.orange_cm.dr_callback_token', 'sekret');
    $payslip = makePayslipForDr();

    $this->postJson('/webhooks/orange-sms/dr?token=sekret', [
        'txnId' => 'txn-123',
        'deliveryStatus' => 'DELIVRD',
    ])->assertOk();

    expect($payslip->fresh()->sms_delivery_status)->toBe(Payslip::SMS_DELIVERY_STATUS_DELIVERED);
});

test('delivery report returns ok with zero processed for unknown txn', function () {
    $this->postJson('/webhooks/orange-sms/dr', [
        'txnId' => 'does-not-exist',
        'deliveryStatus' => 'DELIVRD',
    ])->assertOk()->assertJson(['processed' => 0]);
});
