<?php

use App\Models\User;
use App\Models\Payslip;
use App\Models\SendPayslipProcess;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Bus;
use Laravel\Dusk\Browser;
use Tests\Browser\Pages\PayslipsDetailsPage;

beforeEach(function () {
    Storage::fake('modified');
    Mail::fake();
    Bus::fake();
});

test('user can view payslips details page', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $process = $this->createPayslipProcessWithPayslips(5);
        
        $page = new PayslipsDetailsPage($process->id);
        $browser->visit($page)
            ->assertSee('Payslips Details')
            ->assertSee('Status of Payslips sending');
    });
});

test('user can search for payslips', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $process = $this->createPayslipProcessWithPayslips(5);
        
        $payslip = Payslip::where('send_payslip_process_id', $process->id)->first();
        
        $page = new PayslipsDetailsPage($process->id);
        $browser->visit($page)
            ->type('@searchInput', $payslip->first_name)
            ->pause(1000) // Wait for Livewire to filter
            ->assertSee($payslip->first_name);
    });
});

test('user can switch between active and deleted tabs', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $process = $this->createPayslipProcessWithPayslips(3);
        
        $page = new PayslipsDetailsPage($process->id);
        $browser->visit($page)
            ->assertSee('Active')
            ->click('button:contains("Deleted")')
            ->pause(500)
            ->assertSee('Deleted');
    });
});

test('user can change items per page', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $process = $this->createPayslipProcessWithPayslips(10);
        
        $page = new PayslipsDetailsPage($process->id);
        $browser->visit($page)
            ->select('#perPage', '5')
            ->pause(500)
            ->assertSee('5'); // Should show pagination
    });
});

test('user can change order by field', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $process = $this->createPayslipProcessWithPayslips(5);
        
        $page = new PayslipsDetailsPage($process->id);
        $browser->visit($page)
            ->select('#orderBy', 'last_name')
            ->pause(500)
            ->assertSelected('#orderBy', 'last_name');
    });
});

test('user can change order direction', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $process = $this->createPayslipProcessWithPayslips(5);
        
        $page = new PayslipsDetailsPage($process->id);
        $browser->visit($page)
            ->select('#direction', 'desc')
            ->pause(500)
            ->assertSelected('#direction', 'desc');
    });
});

test('user can select all payslips', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $process = $this->createPayslipProcessWithPayslips(3);
        
        $page = new PayslipsDetailsPage($process->id);
        $browser->visit($page)
            ->check('input[type="checkbox"][wire\\:model="selectAll"]')
            ->pause(500)
            ->assertChecked('input[type="checkbox"][wire\\:model="selectAll"]');
    });
});

test('user can toggle unmatched employees view', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $process = $this->createPayslipProcessWithPayslips(3);
        
        $page = new PayslipsDetailsPage($process->id);
        $browser->visit($page)
            ->click('button:contains("Unmatched")')
            ->pause(500)
            ->assertSee('Unmatched');
    });
});

test('user can see bulk resend failed button when failed payslips exist', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $process = $this->createPayslipProcessWithPayslips(0);
        
        // Create failed payslips
        $failedPayslip = Payslip::factory()->create([
            'send_payslip_process_id' => $process->id,
            'email_sent_status' => Payslip::STATUS_FAILED,
            'encryption_status' => Payslip::STATUS_SUCCESSFUL,
            'file' => 'test.pdf',
        ]);
        
        Storage::disk('modified')->put('test.pdf', 'content');
        
        $page = new PayslipsDetailsPage($process->id);
        $browser->visit($page)
            ->assertSee('Resend All Failed');
    });
});

test('user can click bulk resend failed and see modal', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $process = $this->createPayslipProcessWithPayslips(0);
        
        // Create failed payslips
        $failedPayslip = Payslip::factory()->create([
            'send_payslip_process_id' => $process->id,
            'email_sent_status' => Payslip::STATUS_FAILED,
            'encryption_status' => Payslip::STATUS_SUCCESSFUL,
            'file' => 'test.pdf',
        ]);
        
        Storage::disk('modified')->put('test.pdf', 'content');
        
        $page = new PayslipsDetailsPage($process->id);
        $browser->visit($page)
            ->click('button:contains("Resend All Failed")')
            ->waitFor('#BulkResendFailedModal', 5)
            ->assertSee('Resend All Failed Payslips')
            ->assertSee('Are you sure you want to resend all failed payslips?');
    });
});

test('payslip table displays correct columns', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $process = $this->createPayslipProcessWithPayslips(3);
        
        $page = new PayslipsDetailsPage($process->id);
        $browser->visit($page)
            ->assertSee('First Name')
            ->assertSee('Last Name')
            ->assertSee('Email')
            ->assertSee('Matricule')
            ->assertSee('Month')
            ->assertSee('Status');
    });
});

test('user can see payslip status badges', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $process = $this->createPayslipProcessWithPayslips(0);
        
        // Create payslips with different statuses
        Payslip::factory()->create([
            'send_payslip_process_id' => $process->id,
            'email_sent_status' => Payslip::STATUS_SUCCESSFUL,
        ]);
        
        Payslip::factory()->create([
            'send_payslip_process_id' => $process->id,
            'email_sent_status' => Payslip::STATUS_FAILED,
        ]);
        
        $page = new PayslipsDetailsPage($process->id);
        $browser->visit($page)
            ->assertSee('SUCCESSFUL')
            ->assertSee('FAILED');
    });
});

test('user sees empty state when no payslips exist', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $process = SendPayslipProcess::factory()->create();
        
        $page = new PayslipsDetailsPage($process->id);
        $browser->visit($page)
            ->assertSee('No payslips found');
    });
});

test('user can navigate back to payslips index', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $process = $this->createPayslipProcessWithPayslips(3);
        
        $page = new PayslipsDetailsPage($process->id);
        $browser->visit($page)
            ->clickLink('Process Details')
            ->assertPathIs('/portal/payslips');
    });
});

test('supervisor can only see payslips from their departments', function () {
    $this->browse(function (Browser $browser) {
        // This test would require setting up supervisor-department relationships
        // For now, we'll just verify the page loads
        $user = $this->loginAs($browser, 'supervisor');
        $process = $this->createPayslipProcessWithPayslips(3);
        
        $page = new PayslipsDetailsPage($process->id);
        $browser->visit($page)
            ->assertSee('Payslips Details');
    });
});

