<?php

use App\Models\User;
use App\Models\LeaveType;
use Laravel\Dusk\Browser;

beforeEach(function () {
    // Setup
});

test('user can view leave types page', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        
        $browser->visit('/portal/leaves/types')
            ->assertSee('Leave Types')
            ->assertPathIs('/portal/leaves/types');
    });
});

test('user can search for leave types', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $leaveType = LeaveType::factory()->create(['name' => 'Annual Leave']);
        
        $browser->visit('/portal/leaves/types')
            ->type('#search', 'Annual')
            ->pause(1000)
            ->assertSee('Annual Leave');
    });
});

test('user can create a leave type', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        
        $browser->visit('/portal/leaves/types')
            ->click('button:contains("Create Leave Type")')
            ->pause(500)
            ->waitFor('#LeaveTypeModal', 5)
            ->within('#LeaveTypeModal', function ($modal) {
                $modal->type('#name', 'Sick Leave')
                    ->type('#description', 'For medical reasons')
                    ->press('Save');
            })
            ->pause(1000)
            ->assertSee('Sick Leave');
    });
});

test('user can edit a leave type', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $leaveType = LeaveType::factory()->create(['name' => 'Original Name']);
        
        $browser->visit('/portal/leaves/types')
            ->click("button[wire\\:click='initData({$leaveType->id})']")
            ->pause(500)
            ->waitFor('#LeaveTypeModal', 5)
            ->within('#LeaveTypeModal', function ($modal) {
                $modal->type('#name', 'Updated Name')
                    ->press('Update');
            })
            ->pause(1000)
            ->assertSee('Updated Name');
    });
});

test('user can delete a leave type', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $leaveType = LeaveType::factory()->create();
        
        $browser->visit('/portal/leaves/types')
            ->click("button[wire\\:click='initData({$leaveType->id})']")
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

test('user can switch between active and deleted tabs', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $leaveType = LeaveType::factory()->create();
        $leaveType->delete();
        
        $browser->visit('/portal/leaves/types')
            ->click('button:contains("Deleted")')
            ->pause(500)
            ->assertSee('Deleted');
    });
});

test('user can restore a deleted leave type', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $leaveType = LeaveType::factory()->create();
        $leaveType->delete();
        
        $browser->visit('/portal/leaves/types')
            ->click('button:contains("Deleted")')
            ->pause(500)
            ->click("button[wire\\:click='restore({$leaveType->id})']")
            ->pause(500)
            ->waitFor('#RestoreModal', 5)
            ->within('#RestoreModal', function ($modal) {
                $modal->press('Restore');
            })
            ->pause(1000)
            ->assertSee('restored');
    });
});

test('user can select all leave types', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        LeaveType::factory()->count(3)->create();
        
        $browser->visit('/portal/leaves/types')
            ->check('input[type="checkbox"][wire\\:model="selectAll"]')
            ->pause(500)
            ->assertChecked('input[type="checkbox"][wire\\:model="selectAll"]');
    });
});

test('user can bulk delete leave types', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $leaveTypes = LeaveType::factory()->count(2)->create();
        
        $browser->visit('/portal/leaves/types')
            ->check("input[type='checkbox'][value='{$leaveTypes[0]->id}']")
            ->check("input[type='checkbox'][value='{$leaveTypes[1]->id}']")
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









