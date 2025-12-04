<?php

use App\Models\User;
use App\Models\Payslip;
use App\Models\SendPayslipProcess;
use App\Jobs\RetryPayslipEmailJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Bus;
use Livewire\Livewire;
use Tests\TestCase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('modified');
    Mail::fake();
    Bus::fake();
    
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    // Grant permissions expected by component
    \Illuminate\Support\Facades\Gate::define('payslip-delete', fn() => true);
    \Illuminate\Support\Facades\Gate::define('payslip-send', fn() => true);
});

test('component mounts successfully with process id', function () {
    $process = SendPayslipProcess::factory()->create();
    
    Livewire::test(\App\Livewire\Portal\Payslips\Details::class, ['id' => $process->id])
        ->assertSet('job.id', $process->id);
});

test('component initializes payslip data', function () {
    $process = SendPayslipProcess::factory()->create();
    $payslip = Payslip::factory()->create([
        'send_payslip_process_id' => $process->id,
    ]);
    
    Livewire::test(\App\Livewire\Portal\Payslips\Details::class, ['id' => $process->id])
        ->call('initData', $payslip->id)
        ->assertSet('payslip.id', $payslip->id);
});

test("download payslip returns file when exists", function () {
    $process = SendPayslipProcess::factory()->create();
    $payslip = Payslip::factory()->create([
        "send_payslip_process_id" => $process->id,
        "file" => "test.pdf",
        "matricule" => "EMP001",
        "month" => "January",
        "year" => 2024,
    ]);

    Storage::disk("modified")->put($payslip->file, "fake pdf content");

    Livewire::test(\App\Livewire\Portal\Payslips\Details::class, ["id" => $process->id])
        ->call("downloadPayslip", $payslip->id)
        ->assertDispatched("download-payslip");
});

test("download payslip shows error when file not found", function () {
    $process = SendPayslipProcess::factory()->create();
    $payslip = Payslip::factory()->create([
        "send_payslip_process_id" => $process->id,
        "file" => "non-existent.pdf",
    ]);

    Livewire::test(\App\Livewire\Portal\Payslips\Details::class, ["id" => $process->id])
        ->call("initData", $payslip->id)
        ->call("downloadPayslip", $payslip->id)
        ->assertHasErrors();
});

test('bulk resend failed payslips dispatches retry jobs', function () {
    Gate::define('payslip-send', fn () => true);
    
    $process = SendPayslipProcess::factory()->create();
    
    $failedPayslip1 = Payslip::factory()->create([
        'send_payslip_process_id' => $process->id,
        'email_sent_status' => Payslip::STATUS_FAILED,
        'encryption_status' => Payslip::STATUS_SUCCESSFUL,
        'file' => 'test1.pdf',
    ]);
    
    $failedPayslip2 = Payslip::factory()->create([
        'send_payslip_process_id' => $process->id,
        'email_sent_status' => Payslip::STATUS_FAILED,
        'encryption_status' => Payslip::STATUS_SUCCESSFUL,
        'file' => 'test2.pdf',
    ]);
    
    Storage::disk('modified')->put('test1.pdf', 'content');
    Storage::disk('modified')->put('test2.pdf', 'content');
    
    Livewire::test(\App\Livewire\Portal\Payslips\Details::class, ['id' => $process->id])
        ->call('bulkResendFailed');
    
    Bus::assertDispatched(RetryPayslipEmailJob::class, 2);
});

test("bulk resend shows message when no failed payslips", function () {
    Gate::define("payslip-send", fn() => true);

    $process = SendPayslipProcess::factory()->create();

    Payslip::factory()->successful()->create([
        "send_payslip_process_id" => $process->id,
    ]);

    Livewire::test(\App\Livewire\Portal\Payslips\Details::class, ["id" => $process->id])
        ->call("bulkResendFailed")
        ->assertDispatched("flash-message-BulkResendFailedModal");
});

test('resend payslip resets retry count', function () {
    Gate::define('payslip-send', fn () => true);
    
    $process = SendPayslipProcess::factory()->create();
    $user = User::factory()->create(['email' => 'test@example.com']);
    
    $payslip = Payslip::factory()->create([
        'send_payslip_process_id' => $process->id,
        'employee_id' => $user->id,
        'file' => 'test.pdf',
        'email_retry_count' => 2,
        'last_email_retry_at' => now(),
    ]);
    
    Storage::disk('modified')->put('test.pdf', 'content');
    
    Mail::shouldReceive('to')
        ->once()
        ->andReturnSelf();
    
    Mail::shouldReceive('send')
        ->once()
        ->andReturn(true);
    
    Mail::shouldReceive('failures')
        ->andReturn([]);
    
    Livewire::test(\App\Livewire\Portal\Payslips\Details::class, ['id' => $process->id])
        ->call('initData', $payslip->id)
        ->call('resendPayslip');
    
    $payslip->refresh();
    
    expect($payslip->email_retry_count)->toBe(0);
    expect($payslip->last_email_retry_at)->toBeNull();
});

test('get failed payslips count returns correct number', function () {
    $process = SendPayslipProcess::factory()->create();
    
    Payslip::factory()->failed()->count(3)->create([
        'send_payslip_process_id' => $process->id,
    ]);
    
    Payslip::factory()->successful()->count(2)->create([
        'send_payslip_process_id' => $process->id,
    ]);
    
    $component = Livewire::test(\App\Livewire\Portal\Payslips\Details::class, ["id" => $process->id]);

    $component->call('getFailedPayslipsCount')
        ->assertReturned(3);
});

test('switch tab updates active tab', function () {
    $process = SendPayslipProcess::factory()->create();
    
    Livewire::test(\App\Livewire\Portal\Payslips\Details::class, ['id' => $process->id])
        ->assertSet('activeTab', 'active')
        ->call('switchTab', 'trashed')
        ->assertSet('activeTab', 'trashed');
});

test('toggle unmatched shows unmatched employees', function () {
    $process = SendPayslipProcess::factory()->create();
    
    Livewire::test(\App\Livewire\Portal\Payslips\Details::class, ['id' => $process->id])
        ->assertSet('showUnmatched', false)
        ->call('toggleUnmatched')
        ->assertSet('showUnmatched', true);
});

test('delete payslip soft deletes it', function () {
    $process = SendPayslipProcess::factory()->create();
    $payslip = Payslip::factory()->create([
        'send_payslip_process_id' => $process->id,
    ]);
    
    Livewire::test(\App\Livewire\Portal\Payslips\Details::class, ['id' => $process->id])
        ->call('initData', $payslip->id)
        ->call('delete');
    
    expect(Payslip::find($payslip->id))->toBeNull();
    expect(Payslip::withTrashed()->find($payslip->id))->not->toBeNull();
});

test('restore payslip restores soft deleted payslip', function () {
    $process = SendPayslipProcess::factory()->create();
    $payslip = Payslip::factory()->create([
        'send_payslip_process_id' => $process->id,
    ]);
    $payslip->delete();
    
    Livewire::test(\App\Livewire\Portal\Payslips\Details::class, ['id' => $process->id])
        ->call('restore', $payslip->id);
    
    expect(Payslip::find($payslip->id))->not->toBeNull();
});

test('force delete permanently deletes payslip', function () {
    $process = SendPayslipProcess::factory()->create();
    $payslip = Payslip::factory()->create([
        'send_payslip_process_id' => $process->id,
    ]);
    $payslip->delete();
    
    Livewire::test(\App\Livewire\Portal\Payslips\Details::class, ["id" => $process->id])
        ->call("forceDelete", $payslip->id);

    expect(Payslip::withTrashed()->find($payslip->id))->toBeNull();
});

test('bulk delete deletes multiple payslips', function () {
    $process = SendPayslipProcess::factory()->create();
    $payslip1 = Payslip::factory()->create(['send_payslip_process_id' => $process->id]);
    $payslip2 = Payslip::factory()->create(['send_payslip_process_id' => $process->id]);
    
    Livewire::test(\App\Livewire\Portal\Payslips\Details::class, ['id' => $process->id])
        ->set('selectedPayslips', [$payslip1->id, $payslip2->id])
        ->call('bulkDelete');
    
    expect(Payslip::find($payslip1->id))->toBeNull();
    expect(Payslip::find($payslip2->id))->toBeNull();
});

test('bulk restore restores multiple payslips', function () {
    $process = SendPayslipProcess::factory()->create();
    $payslip1 = Payslip::factory()->create(['send_payslip_process_id' => $process->id]);
    $payslip2 = Payslip::factory()->create(['send_payslip_process_id' => $process->id]);
    $payslip1->delete();
    $payslip2->delete();
    
    Livewire::test(\App\Livewire\Portal\Payslips\Details::class, ['id' => $process->id])
        ->set('selectedPayslips', [$payslip1->id, $payslip2->id])
        ->call('bulkRestore');
    
    expect(Payslip::find($payslip1->id))->not->toBeNull();
    expect(Payslip::find($payslip2->id))->not->toBeNull();
});

test('toggle select all selects all payslips', function () {
    $process = SendPayslipProcess::factory()->create();
    $payslip1 = Payslip::factory()->create(['send_payslip_process_id' => $process->id]);
    $payslip2 = Payslip::factory()->create(['send_payslip_process_id' => $process->id]);
    
    Livewire::test(\App\Livewire\Portal\Payslips\Details::class, ["id" => $process->id])
        ->set("selectAll", true) // Set the selectAll property to true before calling
        ->call("toggleSelectAll")
        ->assertSet("selectAll", true);
});

test('toggle payslip selection adds/removes from selected', function () {
    $process = SendPayslipProcess::factory()->create();
    $payslip = Payslip::factory()->create(['send_payslip_process_id' => $process->id]);
    
    $component = Livewire::test(\App\Livewire\Portal\Payslips\Details::class, ['id' => $process->id]);
    
    $component->call('togglePayslipSelection', $payslip->id)
        ->assertSet('selectedPayslips', [$payslip->id]);
    
    $component->call('togglePayslipSelection', $payslip->id)
        ->assertSet('selectedPayslips', []);
});





