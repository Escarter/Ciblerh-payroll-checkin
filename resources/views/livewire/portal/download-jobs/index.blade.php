<div>
    <x-alert />

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 mb-1">{{__('common.generate')}}</h1>
            <p class="text-muted mb-0">{{__('download_jobs.manage_download_jobs')}}</p>
        </div>
        <div class="d-flex gap-2">
            @can('downloadjob-read')
            <button wire:click="refreshJobs" class="btn btn-outline-secondary">
                <svg class="icon icon-xs me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                {{__('download_jobs.refresh')}}
            </button>
            @endcan
            @can('downloadjob-create')
            <button wire:click="openCreateModal" class="btn btn-primary">
                <svg class="icon icon-xs me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                {{__('reports.generate_new_report')}}
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
                                    <svg class="icon icon-md" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div class="d-sm-none">
                                    <h2 class="fw-extrabold h5">{{__('reports.active_reports')}}</h2>
                                    <h3 class="mb-1">{{$stats['pending'] + $stats['processing']}}</h3>
                                </div>
                            </div>
                            <div class="col-12 col-xl-8 px-xl-0">
                                <a href="#" class="d-none d-sm-block">
                                    <h2 class="h5">{{__('reports.active_reports')}}</h2>
                                    <h3 class="fw-extrabold mb-1">{{$stats['pending'] + $stats['processing']}}</h3>
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
                                    <h2 class="fw-extrabold h5">{{__('download_jobs.completed')}}</h2>
                                    <h3 class="mb-1">{{$stats['completed']}}</h3>
                                </div>
                            </div>
                            <div class="col-12 col-xl-8 px-xl-0">
                                <a href="#" class="d-none d-sm-block">
                                    <h2 class="h5">{{__('download_jobs.completed')}}</h2>
                                    <h3 class="fw-extrabold mb-1">{{$stats['completed']}}</h3>
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
                                    <h2 class="fw-extrabold h5">{{__('common.failed')}}</h2>
                                    <h3 class="mb-1">{{$stats['failed']}}</h3>
                                </div>
                            </div>
                            <div class="col-12 col-xl-8 px-xl-0">
                                <a href="#" class="d-none d-sm-block">
                                    <h2 class="h5">{{__('common.failed')}}</h2>
                                    <h3 class="fw-extrabold mb-1">{{$stats['failed']}}</h3>
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
                                <div class="icon-shape icon-shape-primary rounded me-2 me-sm-0">
                                    <svg class="icon icon-md" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                    </svg>
                                </div>
                                <div class="d-sm-none">
                                    <h2 class="fw-extrabold h5">{{__('download_jobs.total_reports')}}</h2>
                                    <h3 class="mb-1">{{$stats['total']}}</h3>
                                </div>
                            </div>
                            <div class="col-12 col-xl-8 px-xl-0">
                                <a href="#" class="d-none d-sm-block">
                                    <h2 class="h5">{{__('download_jobs.total_reports')}}</h2>
                                    <h3 class="fw-extrabold mb-1">{{$stats['total']}}</h3>
                                </a>

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
                <h6 class="mb-0">{{__('download_jobs.filters_and_search')}}</h6>
                <button wire:click="clearFilters" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-times"></i> {{__('download_jobs.clear_all')}}
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">{{__('download_jobs.report_type')}}</label>
                    <select wire:model.live="jobTypeFilter" class="form-select">
                        <option value="">{{__('download_jobs.all_report_types')}}</option>
                        @foreach($availableJobTypes as $type => $label)
                        <option value="{{$type}}">{{$label}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{__('common.status')}}</label>
                    <select wire:model.live="statusFilter" class="form-select">
                        <option value="">{{__('common.all_statuses')}}</option>
                        <option value="pending">{{__('common.pending')}}</option>
                        <option value="processing">{{__('download_jobs.processing')}}</option>
                        <option value="completed">{{__('download_jobs.completed')}}</option>
                        <option value="failed">{{__('common.failed')}}</option>
                        <option value="cancelled">{{__('download_jobs.cancelled')}}</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">{{__('download_jobs.date_from')}}</label>
                    <input wire:model.live="dateFrom" type="date" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label">{{__('download_jobs.date_to')}}</label>
                    <input wire:model.live="dateTo" type="date" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label">{{__('common.search')}}</label>
                    <input id="download-jobs-search" wire:model.live.debounce.300ms="searchQuery" type="text" class="form-control" placeholder="{{__('common.search_placeholder')}}">
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs and Bulk Actions -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="d-flex gap-2">
            <button wire:click="$set('activeTab', 'active')"
                class="btn {{$activeTab === 'active' ? 'btn-primary' : 'btn-outline-primary'}}"
                type="button">
                <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                {{__('common.active')}}
                <span class="badge {{$activeTab === 'active' ? 'bg-light text-white' : 'bg-primary text-white'}} ms-1">{{$this->activeJobsCount}}</span>
            </button>
            <button wire:click="$set('activeTab', 'scheduled')"
                class="btn {{$activeTab === 'scheduled' ? 'btn-info' : 'btn-outline-info'}}"
                type="button">
                <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                {{__('scheduled_reports.scheduled_reports')}}
                <span class="badge {{$activeTab === 'scheduled' ? 'bg-light text-white' : 'bg-info text-white'}} ms-1">{{$this->scheduledReportsCount}}</span>
            </button>
            <button wire:click="$set('activeTab', 'deleted')"
                class="btn {{$activeTab === 'deleted' ? 'btn-danger' : 'btn-outline-danger'}}"
                type="button">
                <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
                {{__('common.deleted')}}
                <span class="badge {{$activeTab === 'deleted' ? 'bg-light text-white' : 'bg-danger text-white'}} ms-1">{{$this->deletedJobsCount}}</span>
            </button>
        </div>

        <!-- Bulk Actions -->
        @if($activeTab === 'deleted' && count($selectedJobsForDelete) > 0)
        <div class="d-flex gap-2 align-items-center">
            @can('downloadjob-bulkrestore')
            <button data-bs-toggle="modal" data-bs-target="#BulkRestoreModal"
                class="btn btn-sm btn-outline-success d-flex align-items-center"
                title="{{__('common.restore_selected')}}">
                <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                {{__('common.restore')}}
                <span class="badge bg-success text-white ms-1">{{ count($selectedJobsForDelete) }}</span>
            </button>
            @endcan
            @can('downloadjob-delete')
            <button data-bs-toggle="modal" data-bs-target="#BulkForceDeleteModal"
                class="btn btn-sm btn-outline-danger d-flex align-items-center"
                title="{{__('common.permanently_delete')}}">
                <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
                {{__('common.permanently_delete')}}
                <span class="badge bg-danger text-white ms-1">{{ count($selectedJobsForDelete) }}</span>
            </button>
            @endcan
        </div>
        @elseif($activeTab !== 'deleted' && $activeTab !== 'scheduled' && count($selectedJobs) > 0)
        <div class="d-flex gap-2 align-items-center">
            @can('downloadjob-cancel')
            <button wire:click="bulkCancel" class="btn btn-sm btn-outline-warning d-flex align-items-center">
                <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
                {{__('common.cancel')}}
                <span class="badge bg-warning text-white ms-1">{{ count($selectedJobs) }}</span>
            </button>
            @endcan
            @can('downloadjob-bulkdelete')
            <button data-bs-toggle="modal" data-bs-target="#BulkDeleteModal"
                class="btn btn-sm btn-outline-danger d-flex align-items-center"
                title="{{__('common.delete')}}">
                <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
                {{__('common.delete')}}
                <span class="badge bg-danger text-white ms-1">{{ count($selectedJobs) }}</span>
            </button>
            @endcan
        </div>
        @endif
    </div>

    <!-- Jobs Table -->
    @if($activeTab === 'scheduled')
    <!-- Scheduled Reports Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">{{__('scheduled_reports.scheduled_reports')}}</h6>
            @can('downloadjob-create')
            <button wire:click="openCreateModal" class="btn btn-sm btn-primary">
                <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                {{__('scheduled_reports.new_schedule')}}
            </button>
            @endcan
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-bordered mb-0">
                    <thead class="">
                        <tr>
                            <th class="border-0 px-4 py-2 text-muted fw-medium">{{__('scheduled_reports.name')}}</th>
                            <th class="border-0 px-4 py-2 text-muted fw-medium">{{__('scheduled_reports.report_type')}}</th>
                            <th class="border-0 px-4 py-2 text-muted fw-medium">{{__('scheduled_reports.frequency')}}</th>
                            <th class="border-0 px-4 py-2 text-muted fw-medium">{{__('scheduled_reports.recipients')}}</th>
                            <th class="border-0 px-4 py-2 text-muted fw-medium">{{__('scheduled_reports.next_run')}}</th>
                            <th class="border-0 px-4 py-2 text-muted fw-medium">{{__('common.status')}}</th>
                            <th class="border-0 px-4 py-2 text-muted fw-medium">{{__('download_jobs.actions')}}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($scheduledReports as $scheduled)
                        <tr>
                            <td><strong>{{$scheduled->name}}</strong></td>
                            <td>{{$scheduled->job_type_display}}</td>
                            <td>
                                <span class="badge badge-lg bg-info">
                                    @if($scheduled->frequency === 'monthly')
                                        {{__('scheduled_reports.monthly')}} ({{__('scheduled_reports.day')}} {{$scheduled->day_of_month}})
                                    @elseif($scheduled->frequency === 'weekly')
                                        {{__('scheduled_reports.weekly')}}
                                    @else
                                        {{__('scheduled_reports.daily')}}
                                    @endif
                                </span>
                            </td>
                            <td>
                                <small>{{count($scheduled->recipients)}} {{count($scheduled->recipients) === 1 ? __('scheduled_reports.recipient') : __('scheduled_reports.recipients')}}</small>
                            </td>
                            <td>
                                @if($scheduled->next_run_at)
                                <div>{{$scheduled->next_run_at->format('M d, Y')}}</div>
                                <small class="text-muted">{{$scheduled->next_run_at->format('H:i')}}</small>
                                @else
                                <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($scheduled->is_active)
                                <span class="badge badge-lg bg-success">{{__('common.active')}}</span>
                                @else
                                <span class="badge badge-lg bg-secondary">{{__('common.inactive')}}</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    @can('downloadjob-update')
                                    <a href="#" wire:click="toggleScheduledReport({{$scheduled->id}})" class="text-{{$scheduled->is_active ? 'warning' : 'success'}}" title="{{$scheduled->is_active ? __('common.deactivate') : __('common.activate')}}">
                                        @if($scheduled->is_active)
                                        <svg class="icon icon-xs" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        @else
                                        <svg class="icon icon-xs" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        @endif
                                    </a>
                                    @endcan
                                    @can('downloadjob-delete')
                                    <a href="#" wire:click="confirmDeleteScheduledReport({{$scheduled->id}})" class="text-danger" title="{{__('common.delete')}}" data-bs-toggle="modal" data-bs-target="#DeleteModal">
                                        <svg class="icon icon-xs" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </a>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="mb-3">
                                    <i class="fas fa-calendar-alt fa-3x text-muted opacity-50"></i>
                                </div>
                                <h5 class="text-muted">{{__('scheduled_reports.no_scheduled_reports')}}</h5>
                                <p class="text-muted">{{__('scheduled_reports.create_first_schedule_description')}}</p>
                                @can('downloadjob-create')
                                <button wire:click="openCreateModal" class="btn btn-primary">
                                    <svg class="icon icon-xs me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                    {{__('scheduled_reports.create_first_schedule')}}
                                </button>
                                @endcan
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @elseif($activeTab === 'deleted')
    <!-- Deleted Reports (both DownloadJobs and ScheduledReports) -->
    <!-- Deleted DownloadJobs -->
    @if($jobs->count() > 0)
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="mb-0">{{__('download_jobs.deleted_reports')}}</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-bordered mb-0">
                    <thead class="">
                        <tr>
                            <th class="border-0 px-4 py-2 text-muted fw-medium">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" wire:model.live="selectAllForDelete">
                                </div>
                            </th>
                            <th class="border-0 px-4 py-2 text-muted fw-medium">{{__('download_jobs.report_id')}}</th>
                            <th class="border-0 px-4 py-2 text-muted fw-medium">{{__('download_jobs.type')}}</th>
                            <th class="border-0 px-4 py-2 text-muted fw-medium">{{__('common.status')}}</th>
                            <th class="border-0 px-4 py-2 text-muted fw-medium">{{__('download_jobs.created')}}</th>
                            <th class="border-0 px-4 py-2 text-muted fw-medium">{{__('download_jobs.deleted_at')}}</th>
                            <th class="border-0 px-4 py-2 text-muted fw-medium">{{__('download_jobs.actions')}}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($jobs as $job)
                        <tr class="{{in_array($job->id, $selectedJobsForDelete) ? 'table-primary' : ''}}">
                            <td>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" wire:model.live="selectedJobsForDelete" value="{{$job->id}}">
                                </div>
                            </td>
                            <td><strong>#{{Str::limit($job->uuid, 8, '...')}}</strong></td>
                            <td>{{$job->job_type_display}}</td>
                            <td>
                                <span class="badge badge-lg bg-{{$job->status_badge}}">
                                    {{$job->status_display}}
                                </span>
                            </td>
                            <td>
                                <div>{{$job->created_at->format('M d, Y')}}</div>
                                <small class="text-muted">{{$job->created_at->format('H:i')}}</small>
                            </td>
                            <td>
                                <div>{{$job->deleted_at->format('M d, Y')}}</div>
                                <small class="text-muted">{{$job->deleted_at->format('H:i')}}</small>
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    @can('downloadjob-restore')
                                    <a href="#" wire:click="confirmRestore({{$job->id}})" data-bs-toggle="modal" data-bs-target="#RestoreModal" class="text-success" title="{{__('common.restore')}}">
                                        <svg class="icon icon-xs" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
                                        </svg>
                                    </a>
                                    @endcan
                                    @can('downloadjob-delete')
                                    <a href="#" wire:click="forceDelete({{$job->id}})" class="text-danger" title="{{__('common.permanently_delete')}}" data-bs-toggle="modal" data-bs-target="#ForceDeleteModal">
                                        <svg class="icon icon-xs" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </a>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    <!-- Deleted ScheduledReports -->
    @if($scheduledReports->count() > 0)
    <div class="card">
        <div class="card-header">
            <h6 class="mb-0">{{__('scheduled_reports.deleted_scheduled_reports')}}</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-bordered mb-0">
                    <thead class="">
                        <tr>
                            <th class="border-0 px-4 py-2 text-muted fw-medium">{{__('scheduled_reports.name')}}</th>
                            <th class="border-0 px-4 py-2 text-muted fw-medium">{{__('scheduled_reports.report_type')}}</th>
                            <th class="border-0 px-4 py-2 text-muted fw-medium">{{__('scheduled_reports.frequency')}}</th>
                            <th class="border-0 px-4 py-2 text-muted fw-medium">{{__('download_jobs.created')}}</th>
                            <th class="border-0 px-4 py-2 text-muted fw-medium">{{__('download_jobs.deleted_at')}}</th>
                            <th class="border-0 px-4 py-2 text-muted fw-medium">{{__('download_jobs.actions')}}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($scheduledReports as $scheduled)
                        <tr>
                            <td><strong>{{$scheduled->name}}</strong></td>
                            <td>{{$scheduled->job_type_display}}</td>
                            <td>
                                <span class="badge bg-info">
                                    @if($scheduled->frequency === 'monthly')
                                        {{__('scheduled_reports.monthly')}} ({{__('scheduled_reports.day')}} {{$scheduled->day_of_month}})
                                    @elseif($scheduled->frequency === 'weekly')
                                        {{__('scheduled_reports.weekly')}}
                                    @else
                                        {{__('scheduled_reports.daily')}}
                                    @endif
                                </span>
                            </td>
                            <td>
                                <div>{{$scheduled->created_at->format('M d, Y')}}</div>
                                <small class="text-muted">{{$scheduled->created_at->format('H:i')}}</small>
                            </td>
                            <td>
                                <div>{{$scheduled->deleted_at->format('M d, Y')}}</div>
                                <small class="text-muted">{{$scheduled->deleted_at->format('H:i')}}</small>
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    @can('downloadjob-restore')
                                    <a href="#" wire:click="confirmRestoreScheduledReport({{$scheduled->id}})" class="text-success" title="{{__('common.restore')}}" data-bs-toggle="modal" data-bs-target="#RestoreModal">
                                        <svg class="icon icon-xs" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                        </svg>
                                    </a>
                                    @endcan
                                    @can('downloadjob-delete')
                                    <a href="#" wire:click="confirmForceDeleteScheduledReport({{$scheduled->id}})" class="text-danger" title="{{__('common.permanently_delete')}}" data-bs-toggle="modal" data-bs-target="#ForceDeleteModal">
                                        <svg class="icon icon-xs" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </a>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    @if($jobs->count() === 0 && $scheduledReports->count() === 0)
    <div class="card">
        <div class="card-body text-center py-5">
            <div class="mb-3">
                <i class="fas fa-trash fa-3x text-muted opacity-50"></i>
            </div>
            <h5 class="text-muted">{{__('common.no_deleted_items')}}</h5>
            <p class="text-muted">{{__('common.deleted_items_will_appear_here')}}</p>
        </div>
    </div>
    @endif
    @else
    <!-- Regular Reports Table -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-bordered mb-0">
                    <thead class="">
                        <tr>
                            <th class="border-0 px-4 py-2 text-muted fw-medium">
                                <div class="form-check">
                                    @if($activeTab === 'deleted')
                                        <input class="form-check-input" type="checkbox" wire:model.live="selectAllForDelete">
                                    @else
                                        <input class="form-check-input" type="checkbox" wire:model.live="selectAll">
                                    @endif
                                </div>
                            </th>
                            <th class="border-0 px-4 py-2 text-muted fw-medium">{{__('download_jobs.report_id')}}</th>
                            <th class="border-0 px-4 py-2 text-muted fw-medium">{{__('download_jobs.type')}}</th>
                            <th class="border-0 px-4 py-2 text-muted fw-medium">{{__('common.status')}}</th>
                            <th class="border-0 px-4 py-2 text-muted fw-medium">{{__('download_jobs.progress')}}</th>
                            <th class="border-0 px-4 py-2 text-muted fw-medium">{{__('download_jobs.created')}}</th>
                            <th class="border-0 px-4 py-2 text-muted fw-medium">{{__('download_jobs.duration')}}</th>
                            <th class="border-0 px-4 py-2 text-muted fw-medium">{{__('download_jobs.file')}}</th>
                            <th class="border-0 px-4 py-2 text-muted fw-medium">{{__('download_jobs.actions')}}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($jobs as $job)
                        <tr class="{{($activeTab === 'deleted' && in_array($job->id, $selectedJobsForDelete)) || ($activeTab !== 'deleted' && in_array($job->id, $selectedJobs)) ? 'table-primary' : ''}}">
                            <td>
                                <div class="form-check">
                                    @if($activeTab === 'deleted')
                                        <input class="form-check-input" type="checkbox" wire:model.live="selectedJobsForDelete" value="{{$job->id}}">
                                    @else
                                        <input class="form-check-input" type="checkbox" wire:model.live="selectedJobs" value="{{$job->id}}">
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div>
                                    <strong>#{{Str::limit($job->uuid, 8, '...')}}</strong>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0 me-2">
                                        <div class="bg-primary bg-opacity-10 rounded-1 p-1">
                                            <i class="fas fa-{{$job->report_format === 'xlsx' ? 'file-excel' : ($job->report_format === 'pdf' ? 'file-pdf' : 'file-archive')}} text-primary" style="font-size: 12px;"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="fw-semibold">{{$job->job_type_display}}</div>
                                        <small class="text-muted">{{strtoupper($job->report_format)}}</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-lg bg-{{$job->status_badge}}">
                                    {{$job->status_display}}
                                </span>
                            </td>
                            <td>
                                @if($job->status === 'processing')
                                <div class="d-flex align-items-center">
                                    <div class="progress me-2" style="width: 60px; height: 6px;">
                                        <div class="progress-bar progress-bar-striped progress-bar-animated" data-width="{{$job->progress_percentage}}"></div>
                                    </div>
                                    <small>{{$job->progress_percentage}}%</small>
                                </div>
                                <script>
                                    document.addEventListener('DOMContentLoaded', function() {
                                        const progressBar = document.querySelector('[data-width="{{$job->progress_percentage}}"]');
                                        if (progressBar) {
                                            progressBar.style.width = progressBar.dataset.width + '%';
                                        }
                                    });
                                </script>
                                @elseif($job->status === 'completed')
                                <span class="text-success">
                                    <i class="fas fa-check-circle"></i> {{$job->processed_records}}/{{$job->total_records}}
                                </span>
                                @elseif($job->status === 'failed')
                                <span class="text-danger">
                                    <i class="fas fa-exclamation-triangle"></i> {{$job->failed_records}} {{__('failed')}}
                                </span>
                                @else
                                <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <div>{{$job->created_at->format('M d, Y')}}</div>
                                <small class="text-muted">{{$job->created_at->format('H:i')}}</small>
                            </td>
                            <td>
                                @if($job->duration)
                                <span class="text-muted">{{$job->duration}}</span>
                                @else
                                <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($job->status === 'completed' && $job->file_path)
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-file-{{$job->report_format === 'xlsx' ? 'excel' : ($job->report_format === 'pdf' ? 'pdf' : 'archive')}} text-success me-2"></i>
                                    <div>
                                        <div class="small">{{Str::limit($job->file_name, 20, '...')}}</div>
                                        <small class="text-muted">{{$job->formatted_file_size}}</small>
                                    </div>
                                </div>
                                @elseif($job->status === 'failed')
                                <span class="text-danger">
                                    <i class="fas fa-exclamation-triangle"></i> {{__('common.failed')}}
                                </span>
                                @else
                                <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    @if($job->canBeDownloaded())
                                    @can('downloadjob-read')
                                    <a href="#" wire:click="downloadFile({{$job->id}})" class="text-success" title="{{__('download_jobs.download')}}">
                                        <svg class="icon icon-xs" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                    </a>
                                    @endcan
                                    @endif

                                    @if($job->canBeCancelled())
                                    @can('downloadjob-cancel')
                                    <a href="#" wire:click="cancelJob({{$job->id}})" class="text-warning" title="{{__('common.cancel')}}">
                                        <svg class="icon icon-xs" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </a>
                                    @endcan
                                    @endif
                                    @can('downloadjob-read')
                                    <a href="#" wire:click="viewJobDetails({{$job->id}})" class="text-primary" title="{{__('download_jobs.details')}}">
                                        <svg class="icon icon-xs" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </a>
                                    @endcan

                                    @if($activeTab === 'deleted')
                                        @can('downloadjob-restore')
                                        <a href="#" wire:click="confirmRestore({{$job->id}})" data-bs-toggle="modal" data-bs-target="#RestoreModal" class="text-success" title="{{__('common.restore')}}">
                                            <svg class="icon icon-xs" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
                                            </svg>
                                        </a>
                                        @endcan
                                        @can('downloadjob-delete')
                                        <a href="#" wire:click="confirmForceDelete({{$job->id}})" class="text-danger" title="{{__('common.permanently_delete')}}" data-bs-toggle="modal" data-bs-target="#ForceDeleteModal">
                                            <svg class="icon icon-xs" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </a>
                                        @endcan
                                    @elseif($job->canBeDeleted())
                                        @can('downloadjob-delete')
                                        <a href="#" wire:click="confirmDeleteJob({{$job->id}})" class="text-danger" title="{{__('common.delete')}}" data-bs-toggle="modal" data-bs-target="#DeleteModal">
                                            <svg class="icon icon-xs" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                            <td colspan="9" class="text-center py-5">
                                <div class="mb-3">
                                    <i class="fas fa-chart-line fa-3x text-muted opacity-50"></i>
                                </div>
                                <h5 class="text-muted">{{__('download_jobs.no_reports_found')}}</h5>
                                <p class="text-muted">{{__('download_jobs.your_generated_reports_will_appear_here')}}</p>
                                @can('downloadjob-create')
                                <button wire:click="openCreateModal" class="btn btn-primary">
                                    <svg class="icon icon-xs me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                    {{__('download_jobs.create_your_first_report')}}
                                </button>
                                @endcan
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Pagination (only for regular reports, not scheduled) -->
    @if($activeTab !== 'scheduled')
    <div class="d-flex justify-content-center mt-4">
        {{$jobs->links()}}
    </div>
    @endif
    @endif

    <!-- Create Report Modal (includes schedule options) -->
    @include('livewire.portal.download-jobs.create-report-modal')

    <!-- Job Details Modal -->
    @include('livewire.portal.download-jobs.job-details-modal')

    <!-- Delete Confirmation Modal -->
    @include('livewire.partials.delete-modal')

    <!-- Restore Confirmation Modal -->
    @include('livewire.partials.restore-modal')

    <!-- Force Delete Confirmation Modal -->
    @include('livewire.partials.force-delete-modal-generic', ['itemType' => __('common.item')])

    <!-- Bulk Delete Confirmation Modal -->
    @include('livewire.partials.bulk-delete-modal-generic', ['selectedItems' => $selectedJobs, 'itemType' => count($selectedJobs) === 1 ? __('common.item') : __('common.items')])

    <!-- Bulk Restore Confirmation Modal -->
    @include('livewire.partials.bulk-restore-modal', ['selectedItems' => $selectedJobsForDelete, 'itemType' => count($selectedJobsForDelete) === 1 ? __('common.item') : __('common.items')])

    <!-- Bulk Force Delete Confirmation Modal -->
    @include('livewire.partials.bulk-force-delete-modal-generic', ['selectedItems' => $selectedJobsForDelete, 'itemType' => count($selectedJobsForDelete) === 1 ? __('common.item') : __('common.items')])
</div>