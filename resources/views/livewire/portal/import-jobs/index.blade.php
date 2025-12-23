<div>
    <x-alert />

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 mb-1">{{__('common.import_jobs')}}</h1>
            <p class="text-muted mb-0">{{__('import_jobs.manage_import_jobs')}}</p>
        </div>
        <div class="d-flex gap-2">
            <button wire:click="refreshJobs" class="btn btn-outline-secondary">
                <svg class="icon icon-xs me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                {{__('common.refresh')}}
            </button>
            @can('importjob-create')
            <button wire:click="openCreateModal" class="btn btn-primary">
                <svg class="icon icon-xs me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                </svg>
                {{__('import_jobs.create_new_import')}}
            </button>
            @endcan
        </div>
    </div>

    <!-- Stats Cards -->
    <div class='mb-3 mt-0'>
        <div class='row'>
            <div class="col-12 col-sm-6 col-xl-3 mb-2">
                <div class="card border-0 shadow">
                    <div class="card-body">
                        <div class="row d-block d-xl-flex align-items-center">
                            <div class="col-12 col-xl-4 text-xl-center mb-2 mb-xl-0 d-flex align-items-center justify-content-xl-center">
                                <div class="icon-shape icon-shape-warning rounded me-2 me-sm-0">
                                    <svg class="icon icon-md" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div class="d-sm-none">
                                    <h2 class="fw-extrabold h5">{{__('common.pending')}}</h2>
                                    <h3 class="mb-1">{{$stats['pending']}}</h3>
                                </div>
                            </div>
                            <div class="col-12 col-xl-8 px-xl-0">
                                <div class="d-none d-sm-block">
                                    <h2 class="h5">{{__('common.pending')}}</h2>
                                    <h3 class="fw-extrabold mb-1">{{$stats['pending']}}</h3>
                                </div>
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
                                <div class="icon-shape icon-shape-info rounded me-2 me-sm-0">
                                    <svg class="icon icon-md" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                    </svg>
                                </div>
                                <div class="d-sm-none">
                                    <h2 class="fw-extrabold h5">{{__('common.processing')}}</h2>
                                    <h3 class="mb-1">{{$stats['processing']}}</h3>
                                </div>
                            </div>
                            <div class="col-12 col-xl-8 px-xl-0">
                                <div class="d-none d-sm-block">
                                    <h2 class="h5">{{__('common.processing')}}</h2>
                                    <h3 class="fw-extrabold mb-1">{{$stats['processing']}}</h3>
                                </div>
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
                                    <svg class="icon icon-md" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div class="d-sm-none">
                                    <h2 class="fw-extrabold h5">{{__('common.completed')}}</h2>
                                    <h3 class="mb-1">{{$stats['completed']}}</h3>
                                </div>
                            </div>
                            <div class="col-12 col-xl-8 px-xl-0">
                                <div class="d-none d-sm-block">
                                    <h2 class="h5">{{__('common.completed')}}</h2>
                                    <h3 class="fw-extrabold mb-1">{{$stats['completed']}}</h3>
                                </div>
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
                                    <svg class="icon icon-md" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div class="d-sm-none">
                                    <h2 class="fw-extrabold h5">{{__('common.failed')}}</h2>
                                    <h3 class="mb-1">{{$stats['failed']}}</h3>
                                </div>
                            </div>
                            <div class="col-12 col-xl-8 px-xl-0">
                                <div class="d-none d-sm-block">
                                    <h2 class="h5">{{__('common.failed')}}</h2>
                                    <h3 class="fw-extrabold mb-1">{{$stats['failed']}}</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters & Search -->
    <div class="card mb-4">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="mb-0">{{__('import_jobs.filters_and_search')}}</h6>
                <button wire:click="clearFilters" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-times"></i> {{__('import_jobs.clear_all')}}
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">{{__('common.search')}}</label>
                    <input type="text" class="form-control" wire:model.live.debounce.300ms="searchQuery" placeholder="{{__('common.search')}}...">
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{__('common.import_type')}}</label>
                    <select class="form-select" wire:model.live="importTypeFilter">
                        <option value="">{{__('import_jobs.all_import_types')}}</option>
                        @foreach($availableImportTypes as $key => $type)
                            <option value="{{$key}}">{{$type['label']}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{__('common.status')}}</label>
                    <select class="form-select" wire:model.live="statusFilter">
                        <option value="">{{__('common.all')}}</option>
                        <option value="pending">{{__('common.pending')}}</option>
                        <option value="processing">{{__('common.processing')}}</option>
                        <option value="completed">{{__('common.completed')}}</option>
                        <option value="failed">{{__('common.failed')}}</option>
                        <option value="cancelled">{{__('common.cancelled')}}</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{__('common.date_from')}}</label>
                    <input type="date" class="form-control" wire:model.live="dateFrom">
                </div>
            </div>
            <div class="row g-3 mt-1">
                <div class="col-md-3">
                    <label class="form-label">{{__('common.date_to')}}</label>
                    <input type="date" class="form-control" wire:model.live="dateTo">
                </div>
            </div>
        </div>
    </div>

    <!-- Tab Buttons -->
    @if(auth()->user()->can('importjob-bulkdelete') && auth()->user()->can('importjob-bulkrestore'))
    <div class="d-flex justify-content-between align-items-center mb-3">
        <!-- Left Side: Tab Buttons -->
        <div class="d-flex gap-2">
            <button wire:click="switchTab('active')"
                    class="btn {{$activeTab === 'active' ? 'btn-primary' : 'btn-outline-primary'}}"
                    type="button">
                <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
                {{__('common.active')}}
                <span class="badge {{$activeTab === 'active' ? 'bg-light text-white' : 'bg-primary text-white'}} ms-1">{{$activeJobsCount}}</span>
            </button>
            <button wire:click="switchTab('trashed')"
                    class="btn {{$activeTab === 'trashed' ? 'btn-danger' : 'btn-outline-danger'}}"
                    type="button">
                <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
                {{__('common.deleted')}}
                <span class="badge {{$activeTab === 'trashed' ? 'bg-light text-white' : 'bg-danger text-white'}} ms-1">{{$trashedJobsCount}}</span>
            </button>
        </div>

        <!-- Right Side: Select All + Bulk Actions -->
        <div class="d-flex align-items-center gap-2">
            <!-- Select All Button -->
            @if(count($jobs) > 0)
            <button id="select-all-jobs-btn" wire:click="toggleSelectAll"
                    class="btn btn-sm {{ $selectAll ? 'btn-primary' : 'btn-outline-primary' }} d-flex align-items-center">
                <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                {{ $selectAll ? __('import_jobs.deselect_all') : __('import_jobs.select_all') }}
            </button>
            @endif

            <!-- Bulk Actions -->
            @if(count($selectedJobs) > 0)
            @if($activeTab === 'active')
            @can('importjob-cancel')
            <button wire:click="bulkCancel" wire:confirm="{{__('import_jobs.confirm_bulk_cancel')}}" class="btn btn-sm btn-outline-warning d-flex align-items-center">
                <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
                {{__('import_jobs.bulk_cancel')}}
                <span class="badge bg-light text-white ms-1">{{ count($selectedJobs) }}</span>
            </button>
            @endcan

            @can('importjob-bulkdelete')
            <button type="button" class="btn btn-sm btn-danger d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#BulkDeleteModal">
                <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
                {{__('common.move_to_trash')}}
                <span class="badge bg-light text-white ms-1">{{ count($selectedJobs) }}</span>
            </button>
            @endcan
            @else
            @can('importjob-bulkrestore')
            <button wire:click="bulkRestore" class="btn btn-sm btn-outline-success d-flex align-items-center">
                <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                {{__('common.restore_selected')}}
                <span class="badge bg-success text-white ms-1">{{ count($selectedJobs) }}</span>
            </button>
            @endcan

            @can('importjob-delete')
            <button type="button" class="btn btn-sm btn-outline-danger d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#BulkForceDeleteModal">
                <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
                {{__('common.delete_forever')}}
                <span class="badge bg-danger text-white ms-1">{{ count($selectedJobs) }}</span>
            </button>
            @endcan
            @endif

            <button wire:click="$set('selectedJobs', [])"
                    class="btn btn-sm btn-outline-secondary d-flex align-items-center">
                <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
                {{__('common.clear')}}
            </button>
            @endif
        </div>
    </div>
    @endif

    <!-- Jobs Table -->
    <div class="card border-0 shadow">
        <div class="">
            <div class="table-responsive">
                <table class="table table-hover table-bordered mb-0">
                    <thead class="">
                        <tr>
                            <th class="border-0 px-4 py-2 text-muted fw-medium">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" wire:model.live="selectAll">
                                </div>
                            </th>
                            <th class="border-0 px-4 py-2 text-muted fw-medium">{{__('common.type')}}</th>
                            <th class="border-0 px-4 py-2 text-muted fw-medium">{{__('common.file_name')}}</th>
                            <th class="border-0 px-4 py-2 text-muted fw-medium">{{__('common.status')}}</th>
                            <th class="border-0 px-4 py-2 text-muted fw-medium">{{__('common.progress')}}</th>
                            <th class="border-0 px-4 py-2 text-muted fw-medium">{{__('common.created_at')}}</th>
                            <th class="border-0 px-4 py-2 text-muted fw-medium">{{__('common.actions')}}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($jobs as $job)
                        <tr class="{{in_array($job->id, $selectedJobs) ? 'table-primary' : ''}}">
                            <td>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" wire:model="selectedJobs" value="{{$job->id}}">
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-primary badge-lg">{{$job->import_type_display}}</span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div>
                                        <div class="fw-bold text-truncate" style="max-width: 200px;" title="{{$job->file_name}}">
                                            {{$job->file_name}}
                                        </div>
                                        <small class="text-muted">{{$job->uuid}}</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-lg bg-{{$job->status_badge}}">{{$job->status_display}}</span>
                            </td>
                            <td>
                                @if($job->status === 'processing')
                                    <div class="d-flex align-items-center">
                                        <div class="progress flex-grow-1 me-2" style="height: 6px;">
                                            <div class="progress-bar" role="progressbar" style="width: {{$job->progress_percentage}}%"></div>
                                        </div>
                                        <small class="text-muted">{{$job->progress_percentage}}%</small>
                                    </div>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <small class="text-muted">
                                    {{$job->created_at->format('M d, Y H:i')}}
                                    @if($job->duration)
                                        <br><span class="text-success">{{$job->duration}}</span>
                                    @endif
                                </small>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    @if($activeTab === 'active')
                                    <a href="#" wire:click="viewJobDetails({{$job->id}})" title="{{__('import_jobs.view_details')}}">
                                        <svg class="icon icon-xs text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </a>
                                    @if($job->isFailed())
                                    @can('importjob-update')
                                    <a href="#" wire:click="retryModal({{$job->id}})" title="{{__('import_jobs.retry_job')}}">
                                        <svg class="icon icon-xs text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                        </svg>
                                    </a>
                                    @endcan
                                    @endif
                                    @if($job->can_be_cancelled)
                                    @can('importjob-cancel')
                                    <a href="#" wire:click="cancelJob({{$job->id}})" wire:confirm="{{__('import_jobs.confirm_cancel_job')}}" title="{{__('import_jobs.cancel_job')}}">
                                        <svg class="icon icon-xs text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </a>
                                    @endcan
                                    @endif
                                    @can('importjob-delete')
                                    <a href="#" wire:click="$set('job_id', {{$job->id}})" data-bs-toggle="modal" data-bs-target="#DeleteModal" title="{{__('common.move_to_trash')}}">
                                        <svg class="icon icon-xs text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </a>
                                    @endcan
                                    @else
                                    @can('importjob-update')
                                    <a href="#" wire:click="$set('job_id', {{$job->id}})" data-bs-toggle="modal" data-bs-target="#RestoreModal" title="{{__('common.restore')}}">
                                        <svg class="icon icon-xs text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                        </svg>
                                    </a>
                                    @endcan
                                    @can('importjob-delete')
                                    <a href="#" wire:click="$set('job_id', {{$job->id}})" data-bs-toggle="modal" data-bs-target="#ForceDeleteModal" title="{{__('common.delete_forever')}}">
                                        <svg class="icon icon-xs text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </a>
                                    @endcan
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="mb-3">
                                    <i class="fas fa-upload fa-3x text-muted opacity-50"></i>
                                </div>
                                <h5 class="text-muted">{{__('import_jobs.no_jobs_found')}}</h5>
                                <p class="text-muted">{{__('import_jobs.no_jobs_message')}}</p>
                                @can('importjob-create')
                                <button wire:click="openCreateModal" class="btn btn-primary">
                                    <svg class="icon icon-xs me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                    {{__('import_jobs.create_new_import')}}
                                </button>
                                @endcan
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-center mt-4">
                {{$jobs->links()}}
            </div>
        </div>
    </div>

    <!-- Create Import Modal -->
    @include('livewire.portal.import-jobs.create-import-modal')

    <!-- Job Details Modal -->
    @include('livewire.portal.import-jobs.job-details-modal')

    <!-- Bulk Delete Modal -->
    @include('livewire.partials.bulk-delete-modal-generic', ['selectedItems' => $selectedJobs, 'itemType' => count($selectedJobs) === 1 ? __('import_jobs.name') : __('common.import_jobs')])

    <!-- Delete Modal -->
    @include('livewire.partials.delete-modal')

    <!-- Bulk Force Delete Modal -->
    @include('livewire.partials.bulk-force-delete-modal-generic', ['selectedItems' => $selectedJobs, 'itemType' => count($selectedJobs) === 1 ? __('import_jobs.name') : __('common.import_jobs')])

    <!-- Force Delete Modal -->
    @include('livewire.partials.force-delete-modal-generic', ['selectedItems' => $selectedJobs, 'itemType' => __('import_jobs.name')])

    <!-- Retry Confirmation Modal -->
    <div wire:ignore.self class="modal fade" id="RetryModal" tabindex="-1" role="dialog" aria-labelledby="retryModal" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="p-3 p-lg-4">
                        <div class="mb-4 mt-md-0 text-center">
                            <svg class="icon icon-xxl text-success mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            <h1 class="mb-0 h2 fw-bolder">{{__('import_jobs.retry_job_confirmation_title')}}</h1>
                            <p class="pt-2">{{__('import_jobs.retry_job_confirmation_message')}} &#128640;</p>
                        </div>
                        <div class="d-flex justify-content-center">
                            <button type="button" wire:click="confirmRetry" class="btn btn-success mx-3" data-bs-dismiss="modal">{{__('import_jobs.retry_job')}}</button>
                            <button type="button" class="btn btn-gray-300 text-white" data-bs-dismiss="modal">{{__('common.cancel')}}</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Restore Confirmation Modal -->
    <div wire:ignore.self class="modal fade" id="RestoreModal" tabindex="-1" role="dialog" aria-labelledby="restoreModal" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="p-3 p-lg-4">
                        <div class="mb-4 mt-md-0 text-center">
                            <svg class="icon icon-xxl text-success mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            <h1 class="mb-0 h2 fw-bolder">{{__('import_jobs.restore_job_confirmation_title')}}</h1>
                            <p class="pt-2">{{__('import_jobs.confirm_restore_job')}} &#128640;</p>
                        </div>
                        <div class="d-flex justify-content-center">
                            <button type="button" wire:click="restoreJob($wire.job_id)" class="btn btn-success mx-3" data-bs-dismiss="modal">{{__('import_jobs.restore_job')}}</button>
                            <button type="button" class="btn btn-gray-300 text-white" data-bs-dismiss="modal">{{__('common.cancel')}}</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk Retry Confirmation Modal -->
    <div wire:ignore.self class="modal fade" id="BulkRetryModal" tabindex="-1" role="dialog" aria-labelledby="bulkRetryModal" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="p-3 p-lg-4">
                        <div class="mb-4 mt-md-0 text-center">
                            <svg class="icon icon-xxl text-success mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            <h1 class="mb-0 h2 fw-bolder">{{__('import_jobs.bulk_retry_confirmation_title')}}</h1>
                            <p class="pt-2">{{__('import_jobs.bulk_retry_confirmation_message', ['count' => count($selectedJobs ?? [])])}} &#128640;</p>
                        </div>
                        <div class="d-flex justify-content-center">
                            <button type="button" wire:click="bulkRetry" class="btn btn-success mx-3" data-bs-dismiss="modal">{{__('import_jobs.bulk_retry_confirm')}}</button>
                            <button type="button" class="btn btn-gray-300 text-white" data-bs-dismiss="modal">{{__('common.cancel')}}</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript for modal handling -->
    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('show-details-modal', () => {
                const modal = new bootstrap.Modal(document.getElementById('detailsModal'));
                modal.show();
            });

            Livewire.on('hide-details-modal', () => {
                const modal = bootstrap.Modal.getInstance(document.getElementById('detailsModal'));
                if (modal) {
                    modal.hide();
                }
            });

            Livewire.on('show-retry-modal', () => {
                const modal = new bootstrap.Modal(document.getElementById('RetryModal'));
                modal.show();
            });

            Livewire.on('hide-retry-modal', () => {
                const modal = bootstrap.Modal.getInstance(document.getElementById('RetryModal'));
                if (modal) {
                    modal.hide();
                }
            });

            Livewire.on('hide-delete-modal', () => {
                const modal = bootstrap.Modal.getInstance(document.getElementById('DeleteModal'));
                if (modal) {
                    modal.hide();
                }
            });

            Livewire.on('hide-force-delete-modal', () => {
                const modal = bootstrap.Modal.getInstance(document.getElementById('ForceDeleteModal'));
                if (modal) {
                    modal.hide();
                }
            });

        });
    </script>
</div>