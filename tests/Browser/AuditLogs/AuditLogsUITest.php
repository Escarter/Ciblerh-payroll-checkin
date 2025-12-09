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
        AuditLog::factory()->create([
            'user_id' => $user->id,
            'action_perform' => 'Test search action'
        ]);

        $browser->visit('/portal/auditlogs')
            ->type('#search', 'Test search action')
            ->pause(1000)
            ->assertSee('Test search action');
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
            ->assertSee('No records found');
    });
});









