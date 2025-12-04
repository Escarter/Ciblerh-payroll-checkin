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
            ->click('button:contains("Deleted")')
            ->pause(500)
            ->assertSee('Deleted');
    });
});

test('user can delete a payslip process', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $process = SendPayslipProcess::factory()->create();
        
        $browser->visit('/portal/payslips')
            ->click("button[wire\\:click='initData({$process->id})']")
            ->pause(500)
            ->click('button:contains("Delete")')
            ->pause(500)
            ->waitFor('#DeleteModal', 5)
            ->within('#DeleteModal', function ($modal) {
                $modal->press('Delete');
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
            ->click('button:contains("Deleted")')
            ->pause(500)
            ->click("button[wire\\:click='restore({$process->id})']")
            ->pause(500)
            ->waitFor('#RestoreModal', 5)
            ->within('#RestoreModal', function ($modal) {
                $modal->press('Restore');
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
            ->check('input[type="checkbox"][wire\\:model="selectAll"]')
            ->pause(500)
            ->assertChecked('input[type="checkbox"][wire\\:model="selectAll"]');
    });
});

test('user can bulk delete payslip processes', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $processes = SendPayslipProcess::factory()->count(2)->create();
        
        $browser->visit('/portal/payslips')
            ->check("input[type='checkbox'][value='{$processes[0]->id}']")
            ->check("input[type='checkbox'][value='{$processes[1]->id}']")
            ->pause(500)
            ->click('button:contains("Bulk Delete")')
            ->pause(500)
            ->waitFor('#BulkDeleteModal', 5)
            ->within('#BulkDeleteModal', function ($modal) {
                $modal->press('Delete');
            })
            ->pause(1000)
            ->assertSee('moved to trash');
    });
});

test('user sees empty state when no processes exist', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        
        $browser->visit('/portal/payslips')
            ->assertSee('No payslip processes found');
    });
});




