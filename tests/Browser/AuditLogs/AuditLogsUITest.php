<?php

use App\Models\User;
use App\Models\AuditLog;
use Laravel\Dusk\Browser;

beforeEach(function () {
    // Setup
});

test('user can view audit logs page', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        
        $browser->visit('/portal/auditlogs')
            ->assertSee('Audit Logs')
            ->assertPathIs('/portal/auditlogs');
    });
});

test('user can search audit logs', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $log = AuditLog::factory()->create([
            'user_id' => $user->id,
            'action' => 'test_action',
        ]);
        
        $browser->visit('/portal/auditlogs')
            ->type('#search', 'test_action')
            ->pause(1000)
            ->assertSee('test_action');
    });
});

test('user can filter by user', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $otherUser = User::factory()->create();
        AuditLog::factory()->create(['user_id' => $otherUser->id]);
        
        $browser->visit('/portal/auditlogs')
            ->select('#userFilter', (string)$otherUser->id)
            ->pause(500)
            ->assertSelected('#userFilter', (string)$otherUser->id);
    });
});

test('user can filter by action', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        
        $browser->visit('/portal/auditlogs')
            ->select('#actionFilter', 'create')
            ->pause(500)
            ->assertSelected('#actionFilter', 'create');
    });
});

test('user can filter by date range', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        
        $browser->visit('/portal/auditlogs')
            ->type('#start_date', now()->subDays(7)->format('Y-m-d'))
            ->type('#end_date', now()->format('Y-m-d'))
            ->pause(500)
            ->assertInputValue('#start_date', now()->subDays(7)->format('Y-m-d'));
    });
});

test('user can view audit log details', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $log = AuditLog::factory()->create(['user_id' => $user->id]);
        
        $browser->visit('/portal/auditlogs')
            ->click("button[wire\\:click='showDetails({$log->id})']")
            ->pause(500)
            ->waitFor('#DetailsModal', 5)
            ->assertSee($log->action);
    });
});

test('user can export audit logs', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        AuditLog::factory()->count(5)->create(['user_id' => $user->id]);
        
        $browser->visit('/portal/auditlogs')
            ->click('button:contains("Export")')
            ->pause(1000)
            ->assertSee('Export');
    });
});

test('user can change order by field', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        
        $browser->visit('/portal/auditlogs')
            ->select('#orderBy', 'created_at')
            ->pause(500)
            ->assertSelected('#orderBy', 'created_at');
    });
});

test('user can change items per page', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        AuditLog::factory()->count(10)->create(['user_id' => $user->id]);
        
        $browser->visit('/portal/auditlogs')
            ->select('#perPage', '5')
            ->pause(500)
            ->assertSelected('#perPage', '5');
    });
});

test('user sees empty state when no logs exist', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        
        $browser->visit('/portal/auditlogs')
            ->assertSee('No audit logs found');
    });
});









