<?php

use App\Models\User;
use App\Models\Company;
use App\Models\Department;
use App\Models\SendPayslipProcess;
use Laravel\Dusk\Browser;

beforeEach(function () {
    // Setup
});

test('user can view payslips index page', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        
        $browser->visit('/portal/payslips')
            ->assertSee('Payslips')
            ->assertPathIs('/portal/payslips');
    });
});

test('user can see payslip processes list', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $process = SendPayslipProcess::factory()->create();
        
        $browser->visit('/portal/payslips')
            ->assertSee($process->month)
            ->assertSee($process->year);
    });
});

test('user can navigate to payslip details', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $process = SendPayslipProcess::factory()->create();
        
        $browser->visit('/portal/payslips')
            ->click("a[href='/portal/payslips/{$process->id}/details']")
            ->pause(1000)
            ->assertPathIs("/portal/payslips/{$process->id}/details");
    });
});

test('user can switch between active and deleted tabs', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $process = SendPayslipProcess::factory()->create();
        $process->delete();
        
        $browser->visit('/portal/payslips')
            ->click('#deleted-payslips-tab')
            ->pause(500)
            ->assertSee('Deleted');
    });
});

test('user can delete a payslip process', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $process = SendPayslipProcess::factory()->create();
        
        $browser->visit('/portal/payslips')
            ->click("#delete-payslip-{$process->id}")
            ->pause(500)
            ->waitFor('#DeleteModal', 5)
            ->within('#DeleteModal', function ($modal) {
                $modal->press('#confirm-delete-btn');
            })
            ->pause(1000)
            ->assertSee('moved to trash');
    });
});

test('user can restore a deleted payslip process', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $process = SendPayslipProcess::factory()->create();
        $process->delete();
        
        $browser->visit('/portal/payslips')
            ->click('#deleted-payslips-tab')
            ->pause(500)
            ->click("#restore-payslip-{$process->id}")
            ->pause(500)
            ->waitFor('#RestoreModal', 5)
            ->within('#RestoreModal', function ($modal) {
                $modal->press('#confirm-restore-btn');
            })
            ->pause(1000)
            ->assertSee('restored');
    });
});

test('user can select all payslip processes', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        SendPayslipProcess::factory()->count(3)->create();
        
        $browser->visit('/portal/payslips')
            ->check('#select-all-payslips')
            ->pause(500)
            ->assertChecked('#select-all-payslips');
    });
});

test('user can bulk delete payslip processes', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $processes = SendPayslipProcess::factory()->count(2)->create();
        
        $browser->visit('/portal/payslips')
            ->check('#select-all-payslips')
            ->pause(2000)
            ->assertPresent('#bulk-delete-payslips-btn')
            ->click('#bulk-delete-payslips-btn')
            ->pause(2000)
            ->waitFor('#BulkDeleteModal', 10)
            ->within('#BulkDeleteModal', function ($modal) {
                $modal->press('Move to Trash');
            })
            ->pause(2000)
            ->assertSee('moved to trash');
    });
});

test('user sees empty state when no processes exist', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        
        $browser->visit('/portal/payslips')
            ->assertSee('Start processing payslip');
    });
});









