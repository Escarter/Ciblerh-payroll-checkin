<div>
    <x-alert />

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 mb-1">{{__('common.generate')}}</h1>
            <p class="text-muted mb-0">{{__('Generate and manage all your reports')}}</p>
        </div>
        <div class="d-flex gap-2">
            <button wire:click="refreshJobs" class="btn btn-outline-secondary">
                <svg class="icon icon-xs me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                {{__('Refresh')}}
            </button>
            <button wire:click="openCreateModal" class="btn btn-primary">
                <svg class="icon icon-xs me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                {{__('Generate New Report')}}
            </button>
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
                                    <h2 class="fw-extrabold h5">{{__('Active Reports')}}</h2>
                                    <h3 class="mb-1">{{$stats['pending'] + $stats['processing']}}</h3>
                                </div>
                            </div>
                            <div class="col-12 col-xl-8 px-xl-0">
                                <a href="#" class="d-none d-sm-block">
                                    <h2 class="h5">{{__('Active Reports')}}</h2>
                                    <h3 class="fw-extrabold mb-1">{{$stats['pending'] + $stats['processing']}}</h3>
                                </a>
                                <div class="small d-flex mt-1">
                                    <div>{{$stats['pending']}} {{__('pending')}}, {{$stats['processing']}} {{__('processing')}}</div>
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
                                    <svg class="icon icon-md" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                                    </svg>
                                </div>
                                <div class="d-sm-none">
                                    <h2 class="fw-extrabold h5">{{__('Completed')}}</h2>
                                    <h3 class="mb-1">{{$stats['completed']}}</h3>
                                </div>
                            </div>
                            <div class="col-12 col-xl-8 px-xl-0">
                                <a href="#" class="d-none d-sm-block">
                                    <h2 class="h5">{{__('Completed')}}</h2>
                                    <h3 class="fw-extrabold mb-1">{{$stats['completed']}}</h3>
                                </a>
                                <div class="small d-flex mt-1">
                                    <div>{{round(($stats['completed'] / max($stats['total'], 1)) * 100, 1)}}% {{__('success rate')}}</div>
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
                                <div class="small d-flex mt-1">
                                    <div>{{round(($stats['failed'] / max($stats['total'], 1)) * 100, 1)}}% {{__('failure rate')}}</div>
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
                                <div class="icon-shape icon-shape-primary rounded me-2 me-sm-0">
                                    <svg class="icon icon-md" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                    </svg>
                                </div>
                                <div class="d-sm-none">
                                    <h2 class="fw-extrabold h5">{{__('Total Reports')}}</h2>
                                    <h3 class="mb-1">{{$stats['total']}}</h3>
                                </div>
                            </div>
                            <div class="col-12 col-xl-8 px-xl-0">
                                <a href="#" class="d-none d-sm-block">
                                    <h2 class="h5">{{__('Total Reports')}}</h2>
                                    <h3 class="fw-extrabold mb-1">{{$stats['total']}}</h3>
                                </a>
                                <div class="small d-flex mt-1">
                                    <div>{{__('All time')}}</div>
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
                <h6 class="mb-0">{{__('Filters & Search')}}</h6>
                <button wire:click="clearFilters" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-times"></i> {{__('Clear All')}}
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">{{__('Report Type')}}</label>
                    <select wire:model.live="jobTypeFilter" class="form-select">
                        <option value="">{{__('All Report Types')}}</option>
                        @foreach($availableJobTypes as $type => $label)
                        <option value="{{$type}}">{{$label}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{__('common.status')}}</label>
                    <select wire:model.live="statusFilter" class="form-select">
                        <option value="">{{__('All Statuses')}}</option>
                        <option value="pending">{{__('common.pending')}}</option>
                        <option value="processing">{{__('Processing')}}</option>
                        <option value="completed">{{__('Completed')}}</option>
                        <option value="failed">{{__('common.failed')}}</option>
                        <option value="cancelled">{{__('Cancelled')}}</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">{{__('Date From')}}</label>
                    <input wire:model.live="dateFrom" type="date" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label">{{__('Date To')}}</label>
                    <input wire:model.live="dateTo" type="date" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label">{{__('common.search')}}</label>
                    <input wire:model.live.debounce.300ms="searchQuery" type="text" class="form-control" placeholder="{{__('common.search_placeholder')}}">
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
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                {{__('common.active')}}
                <span class="badge {{$activeTab === 'active' ? 'bg-light text-white' : 'bg-primary text-white'}} ms-1">{{$this->activeJobsCount}}</span>
            </button>
            <button wire:click="$set('activeTab', 'completed')"
                class="btn {{$activeTab === 'completed' ? 'btn-success' : 'btn-outline-success'}}"
                type="button">
                <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                {{__('Completed')}}
                <span class="badge {{$activeTab === 'completed' ? 'bg-light text-white' : 'bg-success text-white'}} ms-1">{{$this->completedJobsCount}}</span>
            </button>
            <button wire:click="$set('activeTab', 'failed')"
                class="btn {{$activeTab === 'failed' ? 'btn-danger' : 'btn-outline-danger'}}"
                type="button">
                <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
                {{__('common.failed')}}
                <span class="badge {{$activeTab === 'failed' ? 'bg-light text-white' : 'bg-danger text-white'}} ms-1">{{$this->failedJobsCount}}</span>
            </button>
            <button wire:click="$set('activeTab', 'all')"
                class="btn {{$activeTab === 'all' ? 'btn-secondary' : 'btn-outline-secondary'}}"
                type="button">
                <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                </svg>
                {{__('All Reports')}}
                <span class="badge {{$activeTab === 'all' ? 'bg-light text-white' : 'bg-secondary text-white'}} ms-1">{{$this->allJobsCount}}</span>
            </button>
        </div>

        <!-- Bulk Actions -->
        @if(count($selectedJobs) > 0)
        <div class="d-flex gap-2 align-items-center">
            <span class="badge bg-primary">{{count($selectedJobs)}} {{__('selected')}}</span>
            <button wire:click="bulkCancel" class="btn btn-sm btn-warning">
                <i class="fas fa-times"></i> {{__('common.cancel')}}
            </button>
            <button wire:click="confirmBulkDelete" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#BulkDeleteModal">
                <i class="fas fa-trash"></i> {{__('Delete')}}
            </button>
        </div>
        @endif
    </div>

    <!-- Jobs Table -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-bordered mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="border-0">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" wire:model="selectAll">
                                </div>
                            </th>
                            <th class="border-0">{{__('Report ID')}}</th>
                            <th class="border-0">{{__('Type')}}</th>
                            <th class="border-0">{{__('common.status')}}</th>
                            <th class="border-0">{{__('Progress')}}</th>
                            <th class="border-0">{{__('Created')}}</th>
                            <th class="border-0">{{__('Duration')}}</th>
                            <th class="border-0">{{__('File')}}</th>
                            <th class="border-0">{{__('Actions')}}</th>
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
                                    <a href="#" wire:click="downloadFile({{$job->id}})" class="text-success" title="{{__('Download')}}">
                                        <svg class="icon icon-xs" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                    </a>
                                    @endif

                                    @if($job->canBeCancelled())
                                    <a href="#" wire:click="cancelJob({{$job->id}})" class="text-warning" title="{{__('common.cancel')}}">
                                        <svg class="icon icon-xs" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </a>
                                    @endif
                                    <a href="#" wire:click="viewJobDetails({{$job->id}})" class="text-primary" title="{{__('Details')}}">
                                        <svg class="icon icon-xs" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </a>

                                    @if($job->canBeDeleted())
                                    <a href="#" wire:click="confirmDeleteJob({{$job->id}})" class="text-danger" title="{{__('Delete')}}" data-bs-toggle="modal" data-bs-target="#DeleteModal">
                                        <svg class="icon icon-xs" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </a>
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
                                <h5 class="text-muted">{{__('No reports found')}}</h5>
                                <p class="text-muted">{{__('Your generated reports will appear here')}}</p>
                                <button wire:click="openCreateModal" class="btn btn-primary">
                                    <svg class="icon icon-xs me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                    {{__('Create Your First Report')}}
                                </button>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Pagination -->
    <div class="d-flex justify-content-center mt-4">
        {{$jobs->links()}}
    </div>

    <!-- Create Report Modal -->
    @include('livewire.portal.download-jobs.create-report-modal')

    <!-- Job Details Modal -->
    @include('livewire.portal.download-jobs.job-details-modal')

    <!-- Delete Confirmation Modal -->
    @include('livewire.partials.delete-modal')

    <!-- Bulk Delete Confirmation Modal -->
    @include('livewire.partials.bulk-delete-modal-generic', ['selectedItems' => $selectedJobs, 'itemType' => count($selectedJobs) === 1 ? __('report') : __('reports')])
</div>