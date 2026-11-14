<div>
    <!-- Page Header & Breadcrumb -->
    <div class="py-2 mb-3">
        <nav aria-label="breadcrumb" class="d-none d-md-inline-block">
            <ol class="breadcrumb breadcrumb-dark breadcrumb-transparent mb-2">
                <li class="breadcrumb-item">
                    <a href="{{ route('portal.dashboard') }}" wire:navigate class="text-muted d-inline-flex align-items-center">
                        <svg class="icon icon-xxs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                        {{ __('dashboard.home') }}
                    </a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Superadmin Console</li>
            </ol>
        </nav>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge bg-danger text-uppercase px-2 py-1 fw-bold" style="letter-spacing: .08em; font-size: 0.7rem;">
                        <svg class="icon icon-xxs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        Superadmin Exclusive
                    </span>
                    <span class="badge bg-secondary text-uppercase px-2 py-1" style="font-size: 0.7rem;">
                        Stealth System Hub
                    </span>
                </div>
                <h1 class="h4 mb-1 fw-bold text-gray-900">Platform Features &amp; Superadmin Console</h1>
                <p class="mb-0 text-muted small">
                    Toggle system-wide feature flags, control module visibility across all navigation, and manage stealth superadmin accounts.
                </p>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs Card (Standard Portal Pattern with Generous Spacing) -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-2">
            <ul class="nav nav-pills flex-column flex-sm-row gap-2" role="tablist">
                <li class="nav-item" role="presentation">
                    <button 
                        type="button" 
                        wire:click="$set('activeTab', 'features')" 
                        class="nav-link d-flex align-items-center justify-content-center gap-2 py-2 px-4 {{ $activeTab === 'features' ? 'active fw-semibold shadow-sm' : 'text-gray-700' }}"
                        role="tab"
                        aria-selected="{{ $activeTab === 'features' ? 'true' : 'false' }}"
                    >
                        <svg class="icon icon-xs" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                        <span>Platform Features</span>
                        <span class="badge rounded-pill ms-1 {{ $activeTab === 'features' ? 'bg-white text-primary' : 'bg-gray-200 text-gray-800' }}">
                            {{ $totalFeatures }}
                        </span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button 
                        type="button" 
                        wire:click="$set('activeTab', 'superadmins')" 
                        class="nav-link d-flex align-items-center justify-content-center gap-2 py-2 px-4 {{ $activeTab === 'superadmins' ? 'active fw-semibold shadow-sm bg-danger border-danger text-white' : 'text-gray-700' }}"
                        role="tab"
                        aria-selected="{{ $activeTab === 'superadmins' ? 'true' : 'false' }}"
                    >
                        <svg class="icon icon-xs" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        <span>Manage Superadmins</span>
                        <span class="badge rounded-pill ms-1 {{ $activeTab === 'superadmins' ? 'bg-white text-danger' : 'bg-gray-200 text-gray-800' }}">
                            {{ $superadmins->count() }}
                        </span>
                    </button>
                </li>
            </ul>
        </div>
    </div>

    <!-- Alert Notifications -->
    @if (session()->has('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm d-flex align-items-center gap-2 mb-4" role="alert">
            <svg class="icon icon-xs text-success flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <div class="flex-grow-1 small fw-medium">{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm d-flex align-items-center gap-2 mb-4" role="alert">
            <svg class="icon icon-xs text-danger flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <div class="flex-grow-1 small fw-medium">{{ session('error') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- KPI Metric Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div 
                wire:click="$set('statusFilter', 'all'); $set('activeTab', 'features')"
                class="card border-0 shadow-sm h-100 rounded-3 sa-stat-card cursor-pointer {{ $statusFilter === 'all' && $activeTab === 'features' ? 'sa-stat-active-all' : '' }}"
                title="View all features"
            >
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="sa-stat-icon bg-primary-soft text-primary rounded-3 p-2 d-flex align-items-center justify-content-center flex-shrink-0">
                        <svg class="icon icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    </div>
                    <div>
                        <div class="text-muted small text-uppercase fw-bold" style="font-size: 0.7rem; letter-spacing: .05em;">Total Features</div>
                        <div class="h4 mb-0 fw-bold text-gray-900">{{ $totalFeatures }}</div>
                        <span class="small text-muted" style="font-size: 0.75rem;">Across {{ $categoryCounts->count() }} domains</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div 
                wire:click="$set('statusFilter', 'active'); $set('activeTab', 'features')"
                class="card border-0 shadow-sm h-100 rounded-3 sa-stat-card cursor-pointer {{ $statusFilter === 'active' && $activeTab === 'features' ? 'sa-stat-active-success' : '' }}"
                title="Filter active features"
            >
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="sa-stat-icon bg-success-soft text-success rounded-3 p-2 d-flex align-items-center justify-content-center flex-shrink-0">
                        <svg class="icon icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <div class="text-muted small text-uppercase fw-bold" style="font-size: 0.7rem; letter-spacing: .05em;">Active Features</div>
                        <div class="h4 mb-0 fw-bold text-success">{{ $enabledCount }}</div>
                        <span class="small text-success fw-medium d-inline-flex align-items-center gap-1" style="font-size: 0.75rem;">
                            <span class="sa-pulse-dot bg-success"></span> Visible in UI
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div 
                wire:click="$set('statusFilter', 'disabled'); $set('activeTab', 'features')"
                class="card border-0 shadow-sm h-100 rounded-3 sa-stat-card cursor-pointer {{ $statusFilter === 'disabled' && $activeTab === 'features' ? 'sa-stat-active-warning' : '' }}"
                title="Filter disabled features"
            >
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="sa-stat-icon bg-secondary-soft text-secondary rounded-3 p-2 d-flex align-items-center justify-content-center flex-shrink-0">
                        <svg class="icon icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
                    </div>
                    <div>
                        <div class="text-muted small text-uppercase fw-bold" style="font-size: 0.7rem; letter-spacing: .05em;">Disabled Features</div>
                        <div class="h4 mb-0 fw-bold {{ $disabledCount > 0 ? 'text-warning' : 'text-gray-500' }}">{{ $disabledCount }}</div>
                        <span class="small text-muted" style="font-size: 0.75rem;">Hidden from sidebar</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div 
                wire:click="$set('activeTab', 'superadmins')"
                class="card border-0 shadow-sm h-100 rounded-3 sa-stat-card cursor-pointer {{ $activeTab === 'superadmins' ? 'sa-stat-active-danger' : '' }}"
                title="Manage superadmin accounts"
            >
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="sa-stat-icon bg-danger-soft text-danger rounded-3 p-2 d-flex align-items-center justify-content-center flex-shrink-0">
                        <svg class="icon icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </div>
                    <div>
                        <div class="text-muted small text-uppercase fw-bold" style="font-size: 0.7rem; letter-spacing: .05em;">Stealth Superadmins</div>
                        <div class="h4 mb-0 fw-bold text-danger">{{ $superadmins->count() }}</div>
                        <span class="small text-muted" style="font-size: 0.75rem;">Hidden from staff lists</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- TAB 1: Platform Features Grid --}}
    @if($activeTab === 'features')
        <!-- Search & Filter Toolbar Card -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-body p-3">
                <div class="row g-3 align-items-center">
                    <!-- Search Input -->
                    <div class="col-lg-5 col-md-6">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 text-muted ps-3 pe-2">
                                <svg class="icon icon-xs" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            </span>
                            <input 
                                type="text" 
                                wire:model.live.debounce.250ms="searchFeature" 
                                class="form-control border-start-0 ps-1 py-2 text-gray-900" 
                                placeholder="Search by name, key, or category..."
                            >
                            @if(filled($searchFeature))
                                <button type="button" wire:click="$set('searchFeature', '')" class="btn btn-outline-secondary border-start-0" title="Clear search">
                                    <svg class="icon icon-xxs" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            @endif
                        </div>
                    </div>

                    <!-- Category Domain Filter -->
                    <div class="col-lg-4 col-md-6">
                        <select wire:model.live="selectedCategory" class="form-select py-2 text-gray-800">
                            <option value="all">All Functional Domains ({{ $totalFeatures }})</option>
                            @foreach($categoryCounts as $catName => $count)
                                <option value="{{ $catName }}">{{ ucfirst(str_replace('_', ' ', $catName)) }} ({{ $count }})</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Status Filter Segmented Buttons -->
                    <div class="col-lg-3 col-12 d-flex justify-content-lg-end">
                        <div class="btn-group w-100" role="group">
                            <button 
                                type="button" 
                                wire:click="$set('statusFilter', 'all')" 
                                class="btn btn-sm py-2 {{ $statusFilter === 'all' ? 'btn-primary' : 'btn-outline-secondary' }}"
                            >
                                All ({{ $totalFeatures }})
                            </button>
                            <button 
                                type="button" 
                                wire:click="$set('statusFilter', 'active')" 
                                class="btn btn-sm py-2 {{ $statusFilter === 'active' ? 'btn-success text-white' : 'btn-outline-secondary' }}"
                            >
                                Active ({{ $enabledCount }})
                            </button>
                            <button 
                                type="button" 
                                wire:click="$set('statusFilter', 'disabled')" 
                                class="btn btn-sm py-2 {{ $statusFilter === 'disabled' ? 'btn-warning text-dark' : 'btn-outline-secondary' }}"
                            >
                                Off ({{ $disabledCount }})
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Active Filter Feedback Row -->
                @if(filled($searchFeature) || $selectedCategory !== 'all' || $statusFilter !== 'all')
                    <div class="d-flex flex-wrap align-items-center justify-content-between pt-3 mt-3 border-top gap-2">
                        <div class="small text-muted d-flex flex-wrap align-items-center gap-2">
                            <span>Showing <strong>{{ $filteredCount }}</strong> of <strong>{{ $totalFeatures }}</strong> features</span>
                            @if(filled($searchFeature))
                                <span class="badge bg-light text-dark border">Query: "{{ $searchFeature }}"</span>
                            @endif
                            @if($selectedCategory !== 'all')
                                <span class="badge bg-light text-dark border">Domain: {{ ucfirst(str_replace('_', ' ', $selectedCategory)) }}</span>
                            @endif
                            @if($statusFilter !== 'all')
                                <span class="badge bg-light text-dark border">Status: {{ ucfirst($statusFilter) }}</span>
                            @endif
                        </div>
                        <button type="button" wire:click="resetFilters" class="btn btn-link btn-sm text-primary p-0 text-decoration-none">
                            Reset All Filters
                        </button>
                    </div>
                @endif
            </div>
        </div>

        <!-- Features Grid Grouped by Domain -->
        @if($features->isNotEmpty())
            <div class="d-flex flex-column gap-4">
                @foreach($features as $category => $items)
                    <div class="sa-category-section mb-2" wire:key="category-{{ $category }}">
                        <!-- Category Header Bar -->
                        <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                            <div class="d-flex align-items-center gap-2">
                                <span class="sa-category-icon rounded-2 p-2 d-inline-flex align-items-center justify-content-center 
                                    @if(in_array($category, ['security', 'compliance'])) bg-danger-soft text-danger
                                    @elseif(in_array($category, ['finance', 'payroll'])) bg-success-soft text-success
                                    @elseif($category === 'attendance') bg-info-soft text-info
                                    @elseif($category === 'notifications') bg-warning-soft text-warning
                                    @else bg-primary-soft text-primary
                                    @endif
                                ">
                                    @if($category === 'security')
                                        <svg class="icon icon-xs" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                    @elseif($category === 'compliance')
                                        <svg class="icon icon-xs" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    @elseif($category === 'finance')
                                        <svg class="icon icon-xs" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                    @elseif($category === 'payroll')
                                        <svg class="icon icon-xs" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                                    @elseif($category === 'attendance')
                                        <svg class="icon icon-xs" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    @elseif($category === 'notifications')
                                        <svg class="icon icon-xs" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                                    @elseif($category === 'employees')
                                        <svg class="icon icon-xs" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                    @else
                                        <svg class="icon icon-xs" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                                    @endif
                                </span>
                                <div>
                                    <h2 class="h6 mb-0 fw-bold text-capitalize text-gray-900">{{ str_replace('_', ' ', $category) }} Domain</h2>
                                </div>
                            </div>
                            <span class="badge rounded-pill bg-gray-100 text-gray-700 px-3 py-1 small fw-semibold">
                                {{ $items->where('is_enabled', true)->count() }} / {{ $items->count() }} Active
                            </span>
                        </div>

                        <!-- Feature Cards Grid (Row g-4 for Generous Breathing Room) -->
                        <div class="row g-4">
                            @foreach($items as $feature)
                                <div class="col-md-6 col-xl-4" wire:key="feature-card-{{ $feature->id }}">
                                    <div class="card h-100 border-0 shadow-sm rounded-3 sa-feature-card {{ $feature->is_enabled ? 'sa-feature-active' : 'sa-feature-disabled' }}">
                                        <div class="card-body p-4 d-flex flex-column justify-content-between">
                                            <div>
                                                <!-- Top Bar of Card -->
                                                <div class="d-flex align-items-start justify-content-between gap-2 mb-3">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <span class="sa-feature-dot {{ $feature->is_enabled ? 'bg-success' : 'bg-gray-400' }}"></span>
                                                        <h3 class="h6 mb-0 fw-bold text-gray-900">{{ $feature->name }}</h3>
                                                    </div>
                                                    <div class="form-check form-switch m-0" wire:key="switch-{{ $feature->id }}">
                                                        <input 
                                                            class="form-check-input sa-switch-input" 
                                                            type="checkbox" 
                                                            role="switch" 
                                                            id="feature-{{ $feature->id }}"
                                                            wire:click="toggleFeature({{ $feature->id }})"
                                                            wire:loading.attr="disabled"
                                                            {{ $feature->is_enabled ? 'checked' : '' }}
                                                            title="{{ $feature->is_enabled ? 'Click to disable and hide from sidebar' : 'Click to enable and show in sidebar' }}"
                                                        >
                                                    </div>
                                                </div>

                                                <!-- Description -->
                                                <p class="text-muted small mb-3 sa-feature-desc">
                                                    {{ $feature->description }}
                                                </p>
                                            </div>

                                            <!-- Footer of Card -->
                                            <div class="d-flex align-items-center justify-content-between pt-3 mt-3 border-top border-light">
                                                <span class="badge bg-gray-100 text-gray-700 font-monospace px-2 py-1" style="font-size: 0.72rem; font-weight: 500;">
                                                    feature:{{ $feature->key }}
                                                </span>
                                                <div>
                                                    @if($feature->is_enabled)
                                                        <span class="badge rounded-pill bg-success-soft text-success px-2.5 py-1 fw-semibold d-inline-flex align-items-center gap-1.5" style="font-size: 0.72rem;">
                                                            <span class="sa-pulse-dot bg-success"></span>
                                                            Active
                                                        </span>
                                                    @else
                                                        <span class="badge rounded-pill bg-gray-100 text-gray-600 px-2.5 py-1 fw-semibold" style="font-size: 0.72rem;">
                                                            Hidden
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <!-- Empty State for Features Filter -->
            <div class="card border-0 shadow-sm rounded-3 py-5 text-center my-3">
                <div class="card-body p-4">
                    <div class="sa-empty-icon bg-gray-100 text-gray-500 rounded-circle mx-auto p-3 mb-3 d-inline-flex align-items-center justify-content-center">
                        <svg class="icon icon-md" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <h3 class="h5 fw-bold text-gray-900 mb-1">No Matching Features Found</h3>
                    <p class="text-muted small mb-3">No platform features match your current search query or domain filter.</p>
                    <button type="button" wire:click="resetFilters" class="btn btn-sm btn-outline-primary rounded-pill px-4 py-2">
                        Clear All Filters
                    </button>
                </div>
            </div>
        @endif
    @endif

    {{-- TAB 2: Manage Superadmins --}}
    @if($activeTab === 'superadmins')
        <div class="row g-4">
            <!-- Active Superadmins List Card -->
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm rounded-3 h-100">
                    <div class="card-header bg-transparent border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="h6 mb-0 fw-bold text-gray-900">Active Stealth Superadmins</h2>
                            <small class="text-muted">Accounts possessing top-level administrative authority</small>
                        </div>
                        <span class="badge rounded-pill bg-danger-soft text-danger px-3 py-1 fw-bold">
                            {{ $superadmins->count() }} Authorized
                        </span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-items-center mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="border-0 px-4 py-3 small text-uppercase fw-bold text-muted">User Account</th>
                                        <th class="border-0 px-3 py-3 small text-uppercase fw-bold text-muted">Contact / Status</th>
                                        <th class="border-0 px-4 py-3 small text-uppercase fw-bold text-muted text-end">Privilege</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($superadmins as $sa)
                                        <tr class="align-middle">
                                            <td class="px-4 py-3">
                                                <div class="d-flex align-items-center gap-3">
                                                    <x-user-avatar :user="$sa" size="36" class="rounded-circle shadow-sm" />
                                                    <div>
                                                        <div class="fw-bold text-gray-900 d-flex align-items-center gap-2">
                                                            {{ $sa->name }}
                                                            @if($sa->id === auth()->id())
                                                                <span class="badge bg-primary-soft text-primary px-2 py-0.5 rounded-pill" style="font-size: 0.65rem;">You</span>
                                                            @endif
                                                        </div>
                                                        <small class="text-muted font-monospace" style="font-size: 0.75rem;">
                                                            {{ $sa->matricule ?: 'No Matricule' }}
                                                        </small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-3 py-3">
                                                <span class="small text-gray-800 d-block mb-1">{{ $sa->email }}</span>
                                                <span class="badge bg-success-soft text-success rounded-pill px-2 py-0.5" style="font-size: 0.68rem;">
                                                    <span class="sa-pulse-dot bg-success me-1"></span> Stealth Active
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-end">
                                                @if($sa->id !== auth()->id() && $superadmins->count() > 1)
                                                    <button 
                                                        type="button" 
                                                        wire:click="revokeSuperadmin({{ $sa->id }})" 
                                                        wire:confirm="Revoke top-level Superadmin privileges from {{ $sa->name }}?"
                                                        class="btn btn-sm btn-outline-danger rounded-pill px-3 py-1"
                                                    >
                                                        Revoke
                                                    </button>
                                                @else
                                                    <span class="badge bg-gray-100 text-gray-500 rounded-pill px-3 py-1">
                                                        Permanent
                                                    </span>
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

            <!-- Promote User Card -->
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-header bg-transparent border-bottom py-3 px-4">
                        <h2 class="h6 mb-0 fw-bold text-gray-900">Promote to Superadmin</h2>
                        <small class="text-muted">Grant top-level authority to an existing user account</small>
                    </div>
                    <div class="card-body p-4">
                        <div class="alert alert-primary bg-primary-soft border-0 text-primary p-3 rounded-3 mb-4 d-flex gap-2">
                            <svg class="icon icon-xs flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <div class="small lh-sm">
                                <strong>Stealth Protection:</strong> Once promoted, the user is immediately hidden from regular admins in all staff lists, employee directories, and role assignment menus.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="searchUser" class="form-label small fw-bold text-gray-700">Find User to Promote</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0 text-muted ps-3 pe-2">
                                    <svg class="icon icon-xs" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                </span>
                                <input 
                                    type="text" 
                                    id="searchUser"
                                    wire:model.live.debounce.300ms="searchUser" 
                                    class="form-control border-start-0 ps-1 py-2 text-gray-900" 
                                    placeholder="Search by name, email, or matricule..."
                                >
                            </div>
                            <div class="form-text small text-muted mt-1.5">Type at least 2 characters to display suggestions</div>
                        </div>

                        @if(strlen(trim($searchUser)) >= 2)
                            <div class="list-group list-group-flush border rounded-3 mt-3 shadow-sm">
                                @forelse($candidates as $candidate)
                                    <div class="list-group-item d-flex justify-content-between align-items-center py-3 px-3">
                                        <div class="d-flex align-items-center gap-3">
                                            <x-user-avatar :user="$candidate" size="32" class="rounded-circle shadow-sm" />
                                            <div>
                                                <span class="fw-bold small d-block text-gray-900">{{ $candidate->name }}</span>
                                                <small class="text-muted" style="font-size: .75rem;">{{ $candidate->email }}</small>
                                            </div>
                                        </div>
                                        <div>
                                            @if($candidate->hasRole('superadmin'))
                                                <span class="badge bg-success-soft text-success rounded-pill px-3 py-1">Superadmin</span>
                                            @else
                                                <button 
                                                    type="button" 
                                                    wire:click="makeSuperadmin({{ $candidate->id }})" 
                                                    wire:confirm="Promote {{ $candidate->name }} to Superadmin?"
                                                    class="btn btn-sm btn-primary rounded-pill px-3 py-1 fw-semibold"
                                                >
                                                    Promote
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                @empty
                                    <div class="p-4 text-center text-muted small">
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

@push('styles')
<style>
    /* Stat Cards */
    .sa-stat-card {
        background: #ffffff;
        border: 1px solid rgba(226, 232, 240, 0.8) !important;
        transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
    }
    .sa-stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.06) !important;
        border-color: #cbd5e1 !important;
    }
    .sa-stat-active-all {
        border-color: #3b82f6 !important;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2) !important;
    }
    .sa-stat-active-success {
        border-color: #10b981 !important;
        box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2) !important;
    }
    .sa-stat-active-warning {
        border-color: #f59e0b !important;
        box-shadow: 0 0 0 2px rgba(245, 158, 11, 0.2) !important;
    }
    .sa-stat-active-danger {
        border-color: #ef4444 !important;
        box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.2) !important;
    }
    .sa-stat-icon {
        width: 44px;
        height: 44px;
    }

    /* Soft Color Badges & Backgrounds */
    .bg-primary-soft { background-color: rgba(30, 64, 175, 0.08) !important; color: #1d4ed8 !important; }
    .bg-success-soft { background-color: rgba(16, 185, 129, 0.12) !important; color: #047857 !important; }
    .bg-danger-soft { background-color: rgba(239, 68, 68, 0.12) !important; color: #b91c1c !important; }
    .bg-warning-soft { background-color: rgba(245, 158, 11, 0.12) !important; color: #b45309 !important; }
    .bg-info-soft { background-color: rgba(14, 165, 233, 0.12) !important; color: #0369a1 !important; }
    .bg-secondary-soft { background-color: rgba(100, 116, 139, 0.12) !important; color: #475569 !important; }

    /* Feature Cards */
    .sa-feature-card {
        background: #ffffff;
        border: 1px solid #e2e8f0 !important;
        transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
    }
    .sa-feature-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 24px rgba(0, 0, 0, 0.07) !important;
        border-color: #cbd5e1 !important;
    }
    .sa-feature-active {
        border-top: 3px solid #10b981 !important;
    }
    .sa-feature-disabled {
        border-top: 3px solid #cbd5e1 !important;
        background: #fafbfc;
        opacity: 0.88;
    }
    .sa-feature-desc {
        min-height: 3rem;
        line-height: 1.5;
    }

    /* Dot indicators */
    .sa-feature-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        display: inline-block;
        flex-shrink: 0;
    }
    .sa-pulse-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        display: inline-block;
        flex-shrink: 0;
        box-shadow: 0 0 0 rgba(16, 185, 129, 0.4);
        animation: sa-pulse 2s infinite;
    }
    @keyframes sa-pulse {
        0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
        70% { box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
        100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
    }

    /* Enhanced Switch */
    .sa-switch-input {
        width: 2.75em !important;
        height: 1.4em !important;
        cursor: pointer;
        transition: background-color 0.2s ease-in-out, border-color 0.2s ease-in-out;
    }
    .sa-switch-input:checked {
        background-color: #10b981 !important;
        border-color: #10b981 !important;
    }
</style>
@endpush
