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
    public $searchFeature = '';
    public $selectedCategory = 'all';
    public $statusFilter = 'all'; // 'all', 'active', 'disabled'
    public $activeTab = 'features'; // 'features' or 'superadmins'

    protected $paginationTheme = 'bootstrap';

    public function mount()
    {
        if (!auth()->check() || !auth()->user()->hasRole('superadmin')) {
            abort(403, 'Unauthorized access.');
        }

        PlatformFeature::syncDefaultFeatures();
    }

    public function resetFilters()
    {
        $this->searchFeature = '';
        $this->selectedCategory = 'all';
        $this->statusFilter = 'all';
    }

    public function showToast($message, $type = 'success')
    {
        $this->dispatch('showToast', message: $message, type: $type);
    }

    public function toggleFeature($featureId)
    {
        $feature = PlatformFeature::findOrFail($featureId);
        $feature->is_enabled = !$feature->is_enabled;
        $feature->save();

        \Illuminate\Support\Facades\Cache::forget(PlatformFeature::CACHE_KEY);

        $status = $feature->is_enabled ? 'enabled & visible' : 'disabled & hidden from navigation';
        $this->showToast("Feature '{$feature->name}' is now {$status}.", $feature->is_enabled ? 'success' : 'warning');

        $this->dispatch('feature-toggled', key: $feature->key, enabled: (bool) $feature->is_enabled);
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
            $this->showToast("User '{$user->name}' is now a Superadmin.", 'success');
        } else {
            $this->showToast("User '{$user->name}' is already a Superadmin.", 'info');
        }
    }

    public function revokeSuperadmin($userId)
    {
        $user = User::withoutGlobalScope('hideSuperadmins')->findOrFail($userId);

        if ($user->id === auth()->id()) {
            $this->showToast('You cannot remove your own Superadmin account.', 'danger');
            return;
        }

        $superadminCount = User::withoutGlobalScope('hideSuperadmins')->role('superadmin')->count();
        if ($superadminCount <= 1) {
            $this->showToast('Cannot remove the last remaining Superadmin.', 'danger');
            return;
        }

        $user->removeRole('superadmin');
        \Illuminate\Support\Facades\Cache::forget('is_superadmin_' . $userId);
        \Illuminate\Support\Facades\Cache::forget('superadmin_user_ids');
        $this->showToast("Superadmin privilege removed from '{$user->name}'.", 'success');
    }

    public function removeSuperadmin($userId)
    {
        return $this->revokeSuperadmin($userId);
    }

    public function render()
    {
        $allFeatures = PlatformFeature::orderBy('category')->orderBy('name')->get();
        $totalFeatures = $allFeatures->count();
        $enabledCount = $allFeatures->where('is_enabled', true)->count();
        $disabledCount = $allFeatures->where('is_enabled', false)->count();

        // Unique categories with counts
        $categoryCounts = $allFeatures->groupBy('category')->map->count();

        // Apply filters
        $filteredFeatures = $allFeatures;

        if (filled($this->searchFeature)) {
            $search = mb_strtolower(trim($this->searchFeature));
            $filteredFeatures = $filteredFeatures->filter(function ($item) use ($search) {
                return str_contains(mb_strtolower($item->name), $search)
                    || str_contains(mb_strtolower($item->key), $search)
                    || str_contains(mb_strtolower($item->description ?? ''), $search)
                    || str_contains(mb_strtolower($item->category ?? ''), $search);
            });
        }

        if ($this->selectedCategory !== 'all') {
            $filteredFeatures = $filteredFeatures->where('category', $this->selectedCategory);
        }

        if ($this->statusFilter === 'active') {
            $filteredFeatures = $filteredFeatures->where('is_enabled', true);
        } elseif ($this->statusFilter === 'disabled') {
            $filteredFeatures = $filteredFeatures->where('is_enabled', false);
        }

        $features = $filteredFeatures->groupBy('category');

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
            'activeTab' => $this->activeTab,
            'statusFilter' => $this->statusFilter,
            'searchFeature' => $this->searchFeature,
            'selectedCategory' => $this->selectedCategory,
            'searchUser' => $this->searchUser,
            'features' => $features,
            'totalFeatures' => $totalFeatures,
            'enabledCount' => $enabledCount,
            'disabledCount' => $disabledCount,
            'filteredCount' => $filteredFeatures->count(),
            'categoryCounts' => $categoryCounts,
            'superadmins' => $superadmins,
            'candidates' => $candidates,
        ])->layout('components.layouts.dashboard');
    }
}
