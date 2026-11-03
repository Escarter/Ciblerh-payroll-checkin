<?php

use App\Livewire\Portal\Settings\Appearance;
use App\Models\Setting;
use App\Models\User;
use App\Support\Branding;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Gate::before(fn () => true);
    Role::firstOrCreate(['name' => 'employee', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->syncRoles(['admin']);
    $this->actingAs($user);
    Branding::flush();
});

test('defaults are used when nothing is configured', function () {
    expect(Branding::get('primary'))->toBe(Branding::DEFAULT_PRIMARY)
        ->and(Branding::css())->toContain('--rf-brand:#1d4ba0');
});

test('admin can save appearance and it is applied', function () {
    Livewire::test(Appearance::class)
        ->set('primary_color', '#aa2200')
        ->set('secondary_color', '#22aa00')
        ->set('font_family', 'Inter')
        ->set('timezone', 'Africa/Douala')
        ->call('save')
        ->assertHasNoErrors();

    Branding::flush();
    expect(Branding::get('primary'))->toBe('#aa2200')
        ->and(Branding::get('font'))->toBe('Inter')
        ->and(Branding::get('timezone'))->toBe('Africa/Douala')
        ->and(Branding::css())->toContain('--rf-brand:#aa2200');
});

test('invalid colour is rejected', function () {
    Livewire::test(Appearance::class)->set('primary_color', 'red')->call('save')->assertHasErrors('primary_color');
});

test('reset restores defaults', function () {
    Setting::create(['brand_primary_color' => '#aa2200']);
    Branding::flush();
    Livewire::test(Appearance::class)->call('resetDefaults');
    Branding::flush();
    expect(Branding::get('primary'))->toBe(Branding::DEFAULT_PRIMARY);
});
