<div>
    <div class='p-0'>
        <div class="d-flex justify-content-between w-100 flex-wrap align-items-center">
            <div class="mb-lg-0">
                <nav aria-label="breadcrumb" class="d-none d-md-inline-block">
                    <ol class="breadcrumb breadcrumb-dark breadcrumb-transparent">
                        <li class="breadcrumb-item">
                            <a href="{{ route('portal.dashboard') }}">
                                <svg class="icon icon-xxs" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                                </svg>
                            </a>
                        </li>
                        <li class="breadcrumb-item"><a href="{{route('portal.dashboard')}}" wire:navigate>{{__('dashboard.home')}}</a></li>
                        <li class="breadcrumb-item active" aria-current="page">{{__('common.audit_logs')}}</li>
                    </ol>
                </nav>
                <h1 class="h4 mt-n2 d-flex justify-content-start align-items-end">
                    <svg class="icon me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    {{__('common.audit_logs')}}
                </h1>
                <p class="mt-n1 mx-2">{{__('common.view_all_activities_performed_within_your_space')}} &#x23F0; </p>
            </div>
            <div class="mb-2 mx-3">

            </div>
        </div>
    </div>
    <div class='mb-3 mt-0'>
        <div class='row'>
            <div class="col-12 col-sm-6 col-xl-3 mb-2">
                <div class="card border-0 shadow">
                    <div class="card-body">
                        <div class="row d-block d-xl-flex align-items-center">
                            <div class="col-12 col-xl-4 text-xl-center mb-2 mb-xl-0 d-flex align-items-center justify-content-xl-center">
                                <div class="icon-shape icon-shape-tertiary rounded me-2 me-sm-0">
                                    <svg class="icon icon-md" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                                <div class="d-sm-none">
                                    <h2 class="fw-extrabold h5">{{__('common.total_audit_logs')}}</h2>
                                    <h3 class="mb-1">{{numberFormat(count($logs))}}</h3>
                                </div>
                            </div>
                            <div class="col-12 col-xl-8 px-xl-0">
                                <a href="#" class="d-none d-sm-block">
                                    <h2 class="h5">{{__('common.total_audit_logs')}}</h2>
                                    <h3 class="fw-extrabold mb-1">{{numberFormat(count($logs))}}</h3>
                                </a>
                               
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3 mb-2">
                <div class="card border-0 shadow">
                    <div class="card-body">
                        <div class="row d-block d-xl-flex align-items-center">
                            <div class="col-12 col-xl-4 text-xl-center mb-2 mb-xl-0 d-flex align-items-center justify-content-xl-center">
                                <div class="icon-shape icon-shape-success rounded me-2 me-sm-0">
                                    <svg class="icon icon-md" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                                    </svg>
                                </div>
                                <div class="d-sm-none">
                                    <h2 class="fw-extrabold h5">{{__('common.creation_logs')}}</h2>
                                    <h3 class="mb-1">{{numberFormat($creation_log_count)}}</h3>
                                </div>
                            </div>
                            <div class="col-12 col-xl-8 px-xl-0">
                                <a href="#" class="d-none d-sm-block">
                                    <h2 class="h5">{{__('common.creation_logs')}}</h2>
                                    <h3 class="fw-extrabold mb-1">{{numberFormat($creation_log_count)}}</h3>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3 mb-2">
                <div class="card border-0 shadow">
                    <div class="card-body">
                        <div class="row d-block d-xl-flex align-items-center">
                            <div class="col-12 col-xl-4 text-xl-center mb-2 mb-xl-0 d-flex align-items-center justify-content-xl-center">
                                <div class="icon-shape icon-shape-warning rounded me-2 me-sm-0">
                                    <svg class="icon icon-md" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div class="d-sm-none">
                                    <h2 class="fw-extrabold h5">{{__('common.update_logs')}}</h2>
                                    <h3 class="mb-1">{{numberFormat($update_log_count)}}</h3>
                                </div>
                            </div>
                            <div class="col-12 col-xl-8 px-xl-0">
                                <a href="#" class="d-none d-sm-block">
                                    <h2 class="h5">{{__('common.update_logs')}}</h2>
                                    <h3 class="fw-extrabold mb-1">{{numberFormat($update_log_count)}}</h3>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3 mb-2">
                <div class="card border-0 shadow">
                    <div class="card-body">
                        <div class="row d-block d-xl-flex align-items-center">
                            <div class="col-12 col-xl-4 text-xl-center mb-2 mb-xl-0 d-flex align-items-center justify-content-xl-center">
                                <div class="icon-shape icon-shape-danger rounded me-2 me-sm-0">
                                    <svg class="icon icon-md" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                    </svg>
                                </div>
                                <div class="d-sm-none">
                                    <h2 class="fw-extrabold h5">{{__('common.deletion_logs')}}</h2>
                                    <h3 class="mb-1">{{numberFormat($deletion_log_count)}} </h3>
                                </div>
                            </div>
                            <div class="col-12 col-xl-8 px-xl-0">
                                <a href="#" class="d-none d-sm-block">
                                    <h2 class="h5">{{__('common.deletion_logs')}}</h2>
                                    <h3 class="fw-extrabold mb-1">{{numberFormat($deletion_log_count)}} </h3>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @include('livewire.partials.bulk-delete-modal-generic', ['selectedItems' => $selectedAuditLogs, 'itemType' => count($selectedAuditLogs) === 1 ? __('employees.employee') : __('employees.employees')])
    @include('livewire.partials.bulk-force-delete-modal-generic', ['selectedItems' => $selectedAuditLogs, 'itemType' => count($selectedAuditLogs) === 1 ? __('employees.employee') : __('employees.employees')])
    @include('livewire.partials.restore-modal')
    @include('livewire.partials.bulk-restore-modal')
    
    <!-- Individual Delete Confirmation Modal -->
    <div wire:ignore.self class="modal fade" id="DeleteAuditLogModal" tabindex="-1" role="dialog" aria-labelledby="deleteAuditLogModal" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="p-3 p-lg-4">
                        <div class="mb-4 mt-md-0 text-center">
                            <svg class="icon icon-xxl text-danger mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <h1 class="mb-0 h2 fw-bolder">{{__('common.are_you_sure')}}</h1>
                            <p class="pt-2">{{__('common.you_are_about_to_move_this_audit_log_to_trash_this_action_can_be_undone_later')}}</p>
                        </div>
                        @if($audit_log)
                        <div class="alert alert-light mb-3">
                            <strong>{{__('common.audit_log_details')}}:</strong><br>
                            <small class="text-muted">
                                {{__('common.user')}}: {{$audit_log->user}}<br>
                                {{__('common.action')}}: {{$audit_log->translated_action_type}}<br>
                                {{__('common.date')}}: {{$audit_log->created_at->format('M d, Y H:i')}}
                            </small>
                        </div>
                        @endif
                        <div class="d-flex justify-content-center">
                            <button type="button" wire:click="delete" class="btn btn-danger mx-3" data-bs-dismiss="modal">{{__('common.move_to_trash')}}</button>
                            <button type="button" class="btn btn-gray-300 text-white" data-bs-dismiss="modal">{{__('common.cancel')}}</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Individual Force Delete Confirmation Modal -->
    <div wire:ignore.self class="modal fade" id="ForceDeleteAuditLogModal" tabindex="-1" role="dialog" aria-labelledby="forceDeleteAuditLogModal" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="p-3 p-lg-4">
                        <div class="mb-4 mt-md-0 text-center">
                            <svg class="icon icon-xxl text-danger mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                            <h1 class="mb-0 h2 fw-bolder">{{__('common.permanent_deletion')}}</h1>
                            <p class="pt-2">{{__('common.you_are_about_to_permanently_delete_this_audit_log_from_the_system')}}</p>
                            <p class="text-danger fw-bold">{{__('common.this_action_cannot_be_undone')}}</p>
                        </div>
                        @if($audit_log)
                        <div class="alert alert-danger mb-3">
                            <strong>{{__('common.audit_log_details')}}:</strong><br>
                            <small>
                                {{__('common.user')}}: {{$audit_log->user}}<br>
                                {{__('common.action')}}: {{$audit_log->translated_action_type}}<br>
                                {{__('common.date')}}: {{$audit_log->created_at->format('M d, Y H:i')}}
                            </small>
                        </div>
                        @endif
                        <div class="d-flex justify-content-center">
                            <button type="button" wire:click="forceDelete" class="btn btn-danger mx-3" data-bs-dismiss="modal">{{__('common.delete_forever')}}</button>
                            <button type="button" class="btn btn-gray-300 text-white" data-bs-dismiss="modal">{{__('common.cancel')}}</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <x-alert />

    <!-- Enhanced Filters Card (similar to StratagemAI) -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-12 col-md-6 col-lg-4">
                    <label class="form-label small text-muted fw-medium">{{__('common.search')}}</label>
                    <div class="position-relative">
                        <svg class="position-absolute top-50 start-0 translate-middle-y ms-2 text-muted" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        <input type="text" class="form-control ps-5" placeholder="{{__('common.search_placeholder')}}"
                            wire:model.live.debounce.500ms="query">
                    </div>
                </div>
                @if(count($users) > 0)
                <div class="col-12 col-md-6 col-lg-2">
                    <label class="form-label small text-muted fw-medium">{{__('common.user')}}</label>
                    <select class="form-select" wire:model.live="userFilter">
                        <option value="">{{__('common.all_users')}}</option>
                        @foreach($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-12 col-md-6 col-lg-3">
                    <label class="form-label small text-muted fw-medium">{{__('common.action_type')}}</label>
                    <select class="form-select" wire:model.live="actionFilter">
                        <option value="">{{__('common.all_actions')}}</option>
                        @foreach($actionOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-6 col-lg-2">
                    <label class="form-label small text-muted fw-medium">{{__('common.date_from')}}</label>
                    <input type="date" class="form-control" wire:model.live="dateFrom">
                </div>
                <div class="col-12 col-md-6 col-lg-2">
                    <label class="form-label small text-muted fw-medium">{{__('common.date_to')}}</label>
                    <input type="date" class="form-control" wire:model.live="dateTo">
                </div>
                <div class="col-12 col-md-6 col-lg-2">
                    <label class="form-label small text-muted fw-medium">{{__('common.items_per_page')}}</label>
                    <select class="form-select" wire:model.live="perPage">
                        <option value="5">5</option>
                        <option value="10">10</option>
                        <option value="15">15</option>
                        <option value="20">20</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Table Controls: Bulk Actions (Left) + Tab Buttons (Right) -->
    @if(auth()->user()->can('audit_log-bulkdelete') && auth()->user()->can('audit_log-bulkrestore'))
    <div class="d-flex justify-content-between align-items-center mb-3 px-3">

        <!-- Tab Buttons (Right) -->
        <div class="d-flex gap-2">
            <button class="btn {{ $activeTab === 'active' ? 'btn-primary' : 'btn-outline-primary' }}"
                wire:click="switchTab('active')"
                type="button">
                <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                {{__('common.active')}}
                <span class="badge {{ $activeTab === 'active' ? 'bg-light text-white' : 'bg-primary text-white' }} ms-1">{{ $active_logs ?? 0 }}</span>
            </button>

            <button class="btn {{ $activeTab === 'deleted' ? 'btn-danger' : 'btn-outline-danger' }}"
                wire:click="switchTab('deleted')"
                type="button">
                <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
                {{__('common.deleted')}}
                <span class="badge {{ $activeTab === 'deleted' ? 'bg-light text-white' : 'bg-danger text-white' }} ms-1">{{ $deleted_logs ?? 0 }}</span>
            </button>
        </div>

        <!-- Bulk Actions (Left) -->
        <div>
            @if($activeTab === 'active')
            <!-- Soft Delete Bulk Actions (when items selected for delete) -->
            @if(count($selectedAuditLogs) > 0)
            <div class="d-flex align-items-center gap-2">
                @can('audit_log-bulkdelete')
                <button type="button"
                    class="btn btn-sm btn-outline-danger d-flex align-items-center"
                    title="{{ __('common.move_selected_audit_logs_to_trash') }}"
                    data-bs-toggle="modal"
                    data-bs-target="#BulkDeleteModal">
                    <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    {{__('common.move_to_trash')}}
                    <span class="badge bg-danger text-white ms-1">{{ count($selectedAuditLogs) }}</span>
                </button>
                @endcan

                <button wire:click="$set('selectedAuditLogs', [])"
                    class="btn btn-sm btn-outline-secondary d-flex align-items-center">
                    <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    {{__('common.clear')}}
                </button>
            </div>
            @endif
            @else
            <!-- Deleted Tab Bulk Actions -->
            @if(count($selectedAuditLogs) > 0)
            <div class="d-flex align-items-center gap-2">
                @can('audit_log-bulkrestore')
                <button data-bs-toggle="modal" data-bs-target="#BulkRestoreModal"
                    class="btn btn-sm btn-outline-success d-flex align-items-center me-2"
                    title="{{ __('common.restore_selected_audit_logs') }}">
                    <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    {{__('common.restore_selected')}}
                    <span class="badge bg-success text-white ms-1">{{ count($selectedAuditLogs) }}</span>
                </button>
                @endcan

                @can('audit_log-delete')
                <button type="button"
                    class="btn btn-sm btn-outline-danger d-flex align-items-center"
                    title="{{ __('common.permanently_delete_selected_audit_logs') }}"
                    data-bs-toggle="modal"
                    data-bs-target="#BulkForceDeleteModal">
                    <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    {{__('common.delete_forever')}}
                    <span class="badge bg-danger text-white ms-1">{{ count($selectedAuditLogs) }}</span>
                </button>
                @endcan

                <button wire:click="$set('selectedAuditLogs', [])"
                    class="btn btn-sm btn-outline-secondary d-flex align-items-center">
                    <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    {{__('common.clear')}}
                </button>
            </div>
            @endif
            @endif
        </div>
    </div>
    @endif

    <!-- Main Table Card -->
    <div class="card border-0 shadow-sm">
    
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-bordered mb-0">
                    <thead class="">
                        <tr>
                            <th class="border-0 px-4 py-2 text-muted fw-medium">
                                <input type="checkbox" class="form-check-input" wire:model.live="selectAll" wire:click="toggleSelectAll">
                            </th>
                            <th class="border-0 px-4 py-2 text-muted fw-medium">{{ __('audit_logs.user') }}</th>
                            <th class="border-0 px-4 py-2 text-muted fw-medium">{{ __('audit_logs.action') }}</th>
                            <th class="border-0 px-4 py-2 text-muted fw-medium">{{ __('audit_logs.model') }}</th>
                            <th class="border-0 px-4 py-2 text-muted fw-medium">{{ __('audit_logs.description') }}</th>
                            <th class="border-0 px-4 py-2 text-muted fw-medium">{{ __('audit_logs.date') }}</th>
                            <th class="border-0 px-4 py-2 text-muted fw-medium">{{ __('audit_logs.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                        <tr class="border-bottom border-opacity-25">
                            <td class="px-4 py-2">
                                <input type="checkbox" class="form-check-input" wire:click="toggleAuditLogSelection({{ $log->id }})" {{ in_array($log->id, $selectedAuditLogs) ? 'checked' : '' }}>
                            </td>
                            <td class="px-4 py-2">
                                @php
                                    $userModel = $log->relationLoaded('user') && $log->getRelation('user') ? $log->getRelation('user') : null;
                                    $userName = $userModel ? $userModel->name : ($log->getAttribute('user') ?? 'System');
                                    $userEmail = $userModel ? $userModel->email : null;
                                @endphp
                                @if($userModel || $log->getAttribute('user'))
                                <div class="d-flex align-items-center">
                                    <div class="avatar bg-primary text-white me-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; border-radius: 10%;">
                                        {{ initials($userName) }}
                                    </div>
                                    <div>
                                        <div class="fw-medium text-dark">{{ $userName }}</div>
                                        @if($userEmail)
                                        <small class="text-muted">{{ $userEmail }}</small>
                                        @endif
                                    </div>
                                </div>
                                @else
                                <span class="text-muted">{{ __('audit_logs.system') }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-2">
                                <span class="badge badge-lg bg-{{$log->action_color}} text-white">
                                    {{ $log->translated_action_type }}
                                </span>
                            </td>
                            <td class="px-4 py-2">
                                @if($log->model_type)
                                <span class="text-muted small">{{ class_basename($log->model_type) }}</span>
                                @if($log->model_name)
                                <br><small class="text-dark">{{ \Illuminate\Support\Str::limit($log->model_name, 30) }}</small>
                                @endif
                                @else
                                <span class="text-muted small">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-2">
                                <div class="text-dark small">{{ \Illuminate\Support\Str::limit(strip_tags($log->translated_action_perform), 50) }}</div>
                            </td>
                            <td class="px-4 py-2">
                                <div class="text-muted small">{{ $log->created_at->format('M d, Y H:i') }}</div>
                            </td>
                            <td class="px-4 py-2">
                                <div class="d-flex align-items-center justify-content-center gap-1">
                                    @if($activeTab === 'active')
                                        <!-- Active View Actions -->
                                        <button class="btn btn-link p-0 text-primary" wire:click="openDetailModal({{ $log->id }})" title="{{__('common.view_details')}}">
                                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                        </button>
                                        @can('audit_log-delete')
                                        <button class="btn btn-link p-0 text-danger" wire:click="initData({{ $log->id }})" data-bs-toggle="modal" data-bs-target="#DeleteAuditLogModal" title="{{__('common.delete')}}">
                                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                        @endcan
                                    @else
                                        <!-- Deleted View Actions -->
                                        @can('audit_log-restore')
                                        <button class="btn btn-link p-0 text-success" wire:click.prevent="$set('audit_log_id', {{ $log->id }})" data-bs-toggle="modal" data-bs-target="#RestoreModal" title="{{__('common.restore')}}">
                                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                            </svg>
                                        </button>
                                        @endcan
                                        @can('audit_log-delete')
                                        <button class="btn btn-link p-0 text-danger" wire:click="initData({{ $log->id }})" data-bs-toggle="modal" data-bs-target="#ForceDeleteAuditLogModal" title="{{__('common.delete_forever')}}">
                                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                        @endcan
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="text-muted">
                                    <svg class="mb-3 opacity-50" width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                                    </svg>
                                    <p class="mb-0 fw-medium">{{ __('audit_logs.no_logs_found') }}</p>
                                    <p class="small text-muted">{{ __('audit_logs.try_adjusting_filters') }}</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($logs->hasPages())
        <div class="card-footer bg-white border-top">
            {{ $logs->links() }}
        </div>
        @endif
    </div>

    <!-- Audit Log Detail Modal -->
    @if($showDetailModal && $selectedLog)
    <div class="modal-backdrop fade show" wire:click="closeDetailModal"></div>
    <div wire:ignore.self class="modal side-layout-modal fade show" id="DetailModal" tabindex="-1" role="dialog" aria-labelledby="detailModal" data-bs-backdrop="static" data-bs-keyboard="false" style="display: block;" aria-hidden="false">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document" style="max-width:50%; max-height: 100vh;">
            <div class="modal-content" style="max-height: 100vh; display: flex; flex-direction: column;">
                <div class="modal-header border-0 pb-2 flex-shrink-0">
                    <h1 class="mb-0 h4 fw-bold">{{__('audit_logs.log_details')}}</h1>
                    <button type="button" class="btn-close" wire:click="closeDetailModal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-3 flex-grow-1" style="overflow-y: auto; min-height: 0;">
                    <div class="p-2 p-lg-3">
                    <!-- Basic Information -->
                    <div class="mb-4">
                        <h6 class="fw-semibold mb-3 d-flex align-items-center gap-2">
                            <svg width="18" height="18" fill="currentColor" class="text-primary" viewBox="0 0 24 24">
                                <path d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            {{__('audit_logs.basic_information')}}
                        </h6>
                        <div class="card border shadow-sm">
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small text-muted fw-medium mb-2">{{__('common.user')}}</label>
                                        <div>
                                            @php
                                                $userModel = $selectedLog->relationLoaded('user') && $selectedLog->getRelation('user') ? $selectedLog->getRelation('user') : null;
                                                $userName = $userModel ? $userModel->name : ($selectedLog->getAttribute('user') ?? 'System');
                                            @endphp
                                            @if($userModel || $selectedLog->getAttribute('user'))
                                            <div class="d-flex align-items-center">
                                                <div class="avatar bg-primary text-white me-2 d-flex align-items-center justify-content-center fw-bold rounded" style="width: 40px; height: 40px;">
                                                    {{initials($userName)}}
                                                </div>
                                                <div>
                                                    <div class="fw-medium text-dark">{{ $userName }}</div>
                                                    @if($userModel && $userModel->email)
                                                    <small class="text-muted">{{ $userModel->email }}</small>
                                                    @endif
                                                </div>
                                            </div>
                                            @else
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-3 py-2">
                                                {{__('audit_logs.system')}}
                                            </span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small text-muted fw-medium mb-2">{{__('common.action')}}</label>
                                        <div>
                                            <span class="badge badge-lg bg-{{$selectedLog->action_color}} text-white px-3 py-2 fw-medium">
                                                {{$selectedLog->translated_action_type}}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small text-muted fw-medium mb-2">{{__('common.task_performed_and_date')}}</label>
                                        <div class="text-dark fw-medium" style="line-height: 1.6;">{!! $selectedLog->translated_action_perform !!}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Timestamp Information -->
                    <div class="mb-4">
                        <h6 class="fw-semibold mb-3 d-flex align-items-center gap-2">
                            <svg width="18" height="18" fill="currentColor" class="text-secondary" viewBox="0 0 24 24">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-.5-13H10v6l5.25 3.15.75-1.23-4.5-2.67z"></path>
                            </svg>
                            {{__('audit_logs.timestamp_info')}}
                        </h6>
                        <div class="card border shadow-sm">
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small text-muted fw-medium mb-2">{{__('common.date')}}</label>
                                        <div class="d-flex align-items-center gap-2 flex-wrap">
                                            <span class="fw-medium text-dark">{{ $selectedLog->created_at->format('M d, Y H:i') }}</span>
                                            <span class="text-muted small">({{ $selectedLog->created_at->diffForHumans() }})</span>
                                        </div>
                                    </div>
                                    @if($selectedLog->channel)
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small text-muted fw-medium mb-2">{{__('common.channel')}}</label>
                                        <div>
                                            <code class="bg-light px-2 py-1 rounded text-dark">{{ $selectedLog->channel }}</code>
                                        </div>
                                    </div>
                                    @endif
                                    @if($selectedLog->ip_address)
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small text-muted fw-medium mb-2">{{__('audit_logs.ip_address')}}</label>
                                        <div>
                                            <code class="bg-light px-2 py-1 rounded text-dark">{{ $selectedLog->ip_address }}</code>
                                        </div>
                                    </div>
                                    @endif
                                    @if($selectedLog->url)
                                    <div class="col-12">
                                        <label class="form-label small text-muted fw-medium mb-2">{{__('audit_logs.url')}}</label>
                                        <div>
                                            <code class="bg-light px-2 py-1 rounded text-dark text-break" style="word-break: break-all; font-size: 0.85rem;">{{ $selectedLog->url }}</code>
                                        </div>
                                    </div>
                                    @endif
                                    @if($selectedLog->method)
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small text-muted fw-medium mb-2">{{__('audit_logs.method')}}</label>
                                        <div>
                                            <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 px-3 py-2">{{ $selectedLog->method }}</span>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Model Information -->
                    @if($selectedLog->model_type)
                    <div class="mb-4">
                        <h6 class="fw-semibold mb-3 d-flex align-items-center gap-2">
                            <svg width="18" height="18" fill="currentColor" class="text-info" viewBox="0 0 24 24">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"></path>
                            </svg>
                            {{__('audit_logs.model_information')}}
                        </h6>
                        <div class="card border shadow-sm">
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small text-muted fw-medium mb-2">{{__('audit_logs.model_type')}}</label>
                                        <div>
                                            <code class="bg-light px-2 py-1 rounded text-dark">{{ class_basename($selectedLog->model_type) }}</code>
                                        </div>
                                    </div>
                                    @if($selectedLog->model_id)
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small text-muted fw-medium mb-2">{{__('audit_logs.model_id')}}</label>
                                        <div>
                                            <code class="bg-light px-2 py-1 rounded text-dark">{{ $selectedLog->model_id }}</code>
                                        </div>
                                    </div>
                                    @endif
                                    @if($selectedLog->model_name)
                                    <div class="col-12">
                                        <label class="form-label small text-muted fw-medium mb-2">{{__('audit_logs.model_name')}}</label>
                                        <div class="text-dark fw-medium">{{ $selectedLog->model_name }}</div>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Changes -->
                    @if($selectedLog->changes && count($selectedLog->changes) > 0)
                    <div class="mb-4">
                        <h6 class="fw-semibold mb-3 d-flex align-items-center gap-2">
                            <svg width="18" height="18" fill="currentColor" class="text-warning" viewBox="0 0 24 24">
                                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path>
                            </svg>
                            {{__('audit_logs.changes')}}
                            <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 small ms-2">
                                {{ count($selectedLog->changes) }} {{__('audit_logs.field_changes')}}
                            </span>
                        </h6>
                        <div class="card border shadow-sm">
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="px-3 py-2 fw-medium">{{__('audit_logs.field')}}</th>
                                                <th class="px-3 py-2 fw-medium">{{__('audit_logs.old_value')}}</th>
                                                <th class="px-3 py-2 fw-medium">{{__('audit_logs.new_value')}}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($selectedLog->changes as $field => $change)
                                            <tr>
                                                <td class="px-3 py-2">
                                                    <code class="bg-light px-2 py-1 rounded text-dark">{{ $field }}</code>
                                                </td>
                                                <td class="px-3 py-2">
                                                    <div class="text-danger small" style="word-break: break-word;">
                                                        @if(is_array($change['old'] ?? null))
                                                            <pre class="mb-0 small" style="white-space: pre-wrap;">{{ json_encode($change['old'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                                        @else
                                                            {{ $change['old'] ?? __('audit_logs.n_a') }}
                                                        @endif
                                                    </div>
                                                </td>
                                                <td class="px-3 py-2">
                                                    <div class="text-success small" style="word-break: break-word;">
                                                        @if(is_array($change['new'] ?? null))
                                                            <pre class="mb-0 small" style="white-space: pre-wrap;">{{ json_encode($change['new'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                                        @else
                                                            {{ $change['new'] ?? __('audit_logs.n_a') }}
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Metadata -->
                    @if($selectedLog->metadata)
                    <div class="mb-4">
                        <h6 class="fw-semibold mb-3 d-flex align-items-center gap-2">
                            <svg width="18" height="18" fill="currentColor" class="text-info" viewBox="0 0 24 24">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"></path>
                            </svg>
                            {{__('audit_logs.metadata')}}
                        </h6>
                        <div class="card border shadow-sm">
                            <div class="card-body">
                                <pre class="bg-light p-3 rounded small mb-0" style="max-height: 300px; overflow-y: auto; white-space: pre-wrap; word-wrap: break-word;">{{ json_encode($selectedLog->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
                            </div>
                        </div>
                    </div>
                    @endif
                    </div>
                </div>
                <div class="modal-footer border-1 pt-2 flex-shrink-0">
                    <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="closeDetailModal">{{__('common.close')}}</button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>