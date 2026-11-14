<div>
    <div class="py-4">
        <nav aria-label="breadcrumb" class="d-none d-md-inline-block">
            <ol class="breadcrumb breadcrumb-dark breadcrumb-transparent">
                <li class="breadcrumb-item">
                    <a href="{{ route('portal.dashboard') }}" wire:navigate>
                        <svg class="icon icon-xxs" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    </a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Superadmin Console</li>
            </ol>
        </nav>
        <div class="d-flex justify-content-between w-100 flex-wrap gap-3 align-items-center">
            <div class="mb-3 mb-lg-0">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-danger text-uppercase px-2 py-1" style="letter-spacing: .06em; font-size: .72rem;">Superadmin Exclusive</span>
                    <span class="badge bg-secondary text-uppercase px-2 py-1" style="font-size: .72rem;">Invisible to regular Admins</span>
                </div>
                <h1 class="h3 mt-2 mb-1">Platform Features &amp; Superadmins</h1>
                <p class="mb-0 text-muted">Toggle core features on or off. Disabled features become completely hidden from non-superadmin users across navigation, routes, and search.</p>
            </div>
            <div class="btn-group">
                <button type="button" wire:click="$set('activeTab', 'features')" class="btn btn-sm {{ $activeTab === 'features' ? 'btn-primary' : 'btn-outline-primary' }}">
                    <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                    Platform Features
                </button>
                <button type="button" wire:click="$set('activeTab', 'superadmins')" class="btn btn-sm {{ $activeTab === 'superadmins' ? 'btn-primary' : 'btn-outline-primary' }}">
                    <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    Manage Superadmins ({{ $superadmins->count() }})
                </button>
            </div>
        </div>
    </div>

    @if (session()->has('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- TAB 1: Platform Features --}}
    @if($activeTab === 'features')
        <div class="row g-4">
            @foreach($features as $category => $items)
                <div class="col-12">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <h2 class="h5 mb-0 text-capitalize">{{ str_replace('_', ' ', $category) }} Features</h2>
                        <span class="badge bg-light text-dark border">{{ $items->count() }}</span>
                    </div>

                    <div class="row g-3">
                        @foreach($items as $feature)
                            <div class="col-md-6 col-xl-4">
                                <div class="card h-100 border-0 shadow-sm transition-all {{ $feature->is_enabled ? '' : 'opacity-75' }}" style="border-left: 4px solid {{ $feature->is_enabled ? '#10b981' : '#94a3b8' }} !important;">
                                    <div class="card-body d-flex flex-column justify-content-between p-3">
                                        <div>
                                            <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                                                <h3 class="h6 mb-0 fw-bold">{{ $feature->name }}</h3>
                                                <div class="form-check form-switch m-0">
                                                    <input 
                                                        class="form-check-input" 
                                                        type="checkbox" 
                                                        role="switch" 
                                                        id="feature-{{ $feature->id }}"
                                                        wire:click="toggleFeature({{ $feature->id }})"
                                                        {{ $feature->is_enabled ? 'checked' : '' }}
                                                        style="cursor: pointer; width: 2.5em; height: 1.3em;"
                                                    >
                                                </div>
                                            </div>
                                            <p class="text-muted small mb-3" style="min-height: 2.6rem;">
                                                {{ $feature->description }}
                                            </p>
                                        </div>
                                        <div class="d-flex align-items-center justify-content-between pt-2 border-top">
                                            <code class="small text-muted">{{ $feature->key }}</code>
                                            <span class="badge {{ $feature->is_enabled ? 'bg-success' : 'bg-secondary' }}">
                                                {{ $feature->is_enabled ? 'Active / Visible' : 'Disabled & Hidden' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- TAB 2: Manage Superadmins --}}
    @if($activeTab === 'superadmins')
        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                        <h2 class="h6 mb-0 fw-bold">Active Superadmins</h2>
                        <span class="badge bg-danger">{{ $superadmins->count() }} Superadmin(s)</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th class="border-bottom">User</th>
                                        <th class="border-bottom">Email</th>
                                        <th class="border-bottom text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($superadmins as $sa)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <x-user-avatar :user="$sa" size="32" />
                                                    <div>
                                                        <span class="fw-semibold d-block text-dark">{{ $sa->name }}</span>
                                                        <small class="text-muted">{{ $sa->matricule ?: 'No matricule' }}</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="small">{{ $sa->email }}</span>
                                                @if($sa->id === auth()->id())
                                                    <span class="badge bg-info ms-1">You</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                @if($sa->id !== auth()->id() && $superadmins->count() > 1)
                                                    <button 
                                                        type="button" 
                                                        wire:click="revokeSuperadmin({{ $sa->id }})" 
                                                        wire:confirm="Are you sure you want to revoke Superadmin privileges from {{ $sa->name }}?"
                                                        class="btn btn-sm btn-outline-danger"
                                                    >
                                                        Revoke
                                                    </button>
                                                @else
                                                    <span class="text-muted small">Protected</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent">
                        <h2 class="h6 mb-0 fw-bold">Promote User to Superadmin</h2>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-3">
                            Search for any existing employee, supervisor, manager, or admin to grant them Superadmin authority. Once promoted, they will also become invisible to regular admins.
                        </p>
                        <div class="mb-3">
                            <label for="searchUser" class="form-label small fw-semibold">Search User by Name, Email, or Matricule</label>
                            <input 
                                type="text" 
                                id="searchUser"
                                wire:model.live.debounce.300ms="searchUser" 
                                class="form-control" 
                                placeholder="Type at least 2 characters..."
                            >
                        </div>

                        @if(strlen(trim($searchUser)) >= 2)
                            <div class="list-group list-group-flush border rounded-3 mt-3">
                                @forelse($candidates as $candidate)
                                    <div class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                                        <div class="d-flex align-items-center gap-2">
                                            <x-user-avatar :user="$candidate" size="28" />
                                            <div>
                                                <span class="fw-semibold small d-block">{{ $candidate->name }}</span>
                                                <small class="text-muted" style="font-size: .75rem;">{{ $candidate->email }}</small>
                                            </div>
                                        </div>
                                        <div>
                                            @if($candidate->hasRole('superadmin'))
                                                <span class="badge bg-success">Already Superadmin</span>
                                            @else
                                                <button 
                                                    type="button" 
                                                    wire:click="makeSuperadmin({{ $candidate->id }})" 
                                                    wire:confirm="Promote {{ $candidate->name }} to Superadmin?"
                                                    class="btn btn-sm btn-primary py-1 px-2"
                                                >
                                                    Promote
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                @empty
                                    <div class="p-3 text-center text-muted small">
                                        No users matching "{{ $searchUser }}"
                                    </div>
                                @endforelse
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
