<?php

namespace App\Livewire\Portal\Superadmin;

use Livewire\Component;
use App\Models\PlatformFeature;
use App\Models\User;
use App\Models\Role;
use Livewire\WithPagination;

class FeatureManagement extends Component
{
    use WithPagination;

    public $searchUser = '';
    public $activeTab = 'features'; // 'features' or 'superadmins'

    protected $paginationTheme = 'bootstrap';

    public function mount()
    {
        if (!auth()->check() || !auth()->user()->hasRole('superadmin')) {
            abort(403, 'Unauthorized access.');
        }
    }

    public function toggleFeature($featureId)
    {
        $feature = PlatformFeature::findOrFail($featureId);
        $feature->is_enabled = !$feature->is_enabled;
        $feature->save();

        session()->flash('success', __("Feature ':name' is now :status.", [
            'name' => $feature->name,
            'status' => $feature->is_enabled ? 'enabled' : 'disabled',
        ]));
    }

    public function makeSuperadmin($userId)
    {
        $user = User::withoutGlobalScope('hideSuperadmins')->findOrFail($userId);
        $role = Role::withoutGlobalScope('hideSuperadminRole')->where('name', 'superadmin')->first();

        if (!$role) {
            $role = Role::create(['name' => 'superadmin', 'guard_name' => 'web']);
        }

        if (!$user->hasRole('superadmin')) {
            $user->assignRole($role);
            \Illuminate\Support\Facades\Cache::forget('is_superadmin_' . $userId);
            \Illuminate\Support\Facades\Cache::forget('superadmin_user_ids');
            session()->flash('success', "User '{$user->name}' ({$user->email}) is now a Superadmin.");
        }
    }

    public function revokeSuperadmin($userId)
    {
        $user = User::withoutGlobalScope('hideSuperadmins')->findOrFail($userId);

        if ($user->id === auth()->id()) {
            session()->flash('error', 'You cannot revoke your own Superadmin privileges.');
            return;
        }

        $superadminCount = User::withoutGlobalScope('hideSuperadmins')->role('superadmin')->count();
        if ($superadminCount <= 1) {
            session()->flash('error', 'Cannot revoke the last remaining Superadmin.');
            return;
        }

        $user->removeRole('superadmin');
        \Illuminate\Support\Facades\Cache::forget('is_superadmin_' . $userId);
        \Illuminate\Support\Facades\Cache::forget('superadmin_user_ids');
        session()->flash('success', "Superadmin privilege revoked from '{$user->name}'.");
    }

    public function render()
    {
        $features = PlatformFeature::orderBy('category')->orderBy('name')->get()->groupBy('category');

        $superadmins = User::withoutGlobalScope('hideSuperadmins')
            ->role('superadmin')
            ->get();

        $candidates = collect();
        if (strlen(trim($this->searchUser)) >= 2) {
            $candidates = User::withoutGlobalScope('hideSuperadmins')
                ->where(function ($q) {
                    $q->where('first_name', 'like', '%' . $this->searchUser . '%')
                      ->orWhere('last_name', 'like', '%' . $this->searchUser . '%')
                      ->orWhere('email', 'like', '%' . $this->searchUser . '%')
                      ->orWhere('matricule', 'like', '%' . $this->searchUser . '%');
                })
                ->limit(8)
                ->get();
        }

        return view('livewire.portal.superadmin.feature-management', [
            'features' => $features,
            'superadmins' => $superadmins,
            'candidates' => $candidates,
        ])->layout('components.layouts.app');
    }
}
