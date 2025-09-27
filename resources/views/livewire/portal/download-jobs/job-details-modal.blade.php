<!-- Job Details Modal -->
<div wire:ignore.self class="modal side-layout-modal fade" id="jobDetailsModal" tabindex="-1" aria-labelledby="modal-form" data-bs-backdrop="static" data-bs-keyboard="false" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered " role="document" style="max-width:65%;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{__('Job Details')}} @if($selectedJob)- #{{$selectedJob->uuid}}@endif</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" wire:click="closeDetailsModal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                @if($selectedJob)
                <div class="p-3 p-lg-4">
                    <div class="mb-4 mt-md-0">
                        <p>{{__('View job status and details')}} &#128522;</p>
                    </div>
                <div class="row">
                    <div class="col-md-6">
                        <h6>{{__('Job Information')}}</h6>
                        <table class="table table-sm">
                            <tr>
                                <td><strong>{{__('Type')}}:</strong></td>
                                <td>{{$selectedJob->job_type_display}}</td>
                            </tr>
                            <tr>
                                <td><strong>{{__('Format')}}:</strong></td>
                                <td>{{strtoupper($selectedJob->report_format)}}</td>
                            </tr>
                            <tr>
                                <td><strong>{{__('Status')}}:</strong></td>
                                <td>
                                    <span class="badge badge-{{$selectedJob->status_badge}}">
                                        {{$selectedJob->status_display}}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>{{__('Created')}}:</strong></td>
                                <td>{{$selectedJob->created_at->format('M d, Y H:i:s')}}</td>
                            </tr>
                            @if($selectedJob->started_at)
                            <tr>
                                <td><strong>{{__('Started')}}:</strong></td>
                                <td>{{$selectedJob->started_at->format('M d, Y H:i:s')}}</td>
                            </tr>
                            @endif
                            @if($selectedJob->completed_at)
                            <tr>
                                <td><strong>{{__('Completed')}}:</strong></td>
                                <td>{{$selectedJob->completed_at->format('M d, Y H:i:s')}}</td>
                            </tr>
                            @endif
                            @if($selectedJob->duration)
                            <tr>
                                <td><strong>{{__('Duration')}}:</strong></td>
                                <td>{{$selectedJob->duration}}</td>
                            </tr>
                            @endif
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6>{{__('Progress & Results')}}</h6>
                        <table class="table table-sm">
                            <tr>
                                <td><strong>{{__('Total Records')}}:</strong></td>
                                <td>{{$selectedJob->total_records}}</td>
                            </tr>
                            <tr>
                                <td><strong>{{__('Processed')}}:</strong></td>
                                <td>{{$selectedJob->processed_records}}</td>
                            </tr>
                            <tr>
                                <td><strong>{{__('Failed')}}:</strong></td>
                                <td>{{$selectedJob->failed_records}}</td>
                            </tr>
                            @if($selectedJob->file_size)
                            <tr>
                                <td><strong>{{__('File Size')}}:</strong></td>
                                <td>{{$selectedJob->formatted_file_size}}</td>
                            </tr>
                            @endif
                            @if($selectedJob->progress_percentage > 0)
                            <tr>
                                <td><strong>{{__('Progress')}}:</strong></td>
                                <td>{{$selectedJob->progress_percentage}}%</td>
                            </tr>
                            @endif
                        </table>

                        @if($selectedJob->status === 'processing')
                        <div class="mt-3">
                            <div class="progress">
                                <div class="progress-bar" data-width="{{ $selectedJob->progress_percentage }}" id="progress-bar-{{ $selectedJob->id }}"></div>
                            </div>
                        </div>
                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                const progressBar = document.getElementById('progress-bar-{{ $selectedJob->id }}');
                                if (progressBar) {
                                    progressBar.style.width = progressBar.dataset.width + '%';
                                }
                            });
                        </script>
                        @endif
                    </div>
                </div>

                @if($selectedJob->filters)
                <div class="mt-4">
                    <h6>{{__('Filters Applied')}}</h6>
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                @if(isset($selectedJob->filters['company_name']))
                                <div class="col-md-6">
                                    <strong>{{__('Company')}}:</strong> {{$selectedJob->filters['company_name']}}
                                </div>
                                @endif
                                @if(isset($selectedJob->filters['department_name']))
                                <div class="col-md-6">
                                    <strong>{{__('Department')}}:</strong> {{$selectedJob->filters['department_name']}}
                                </div>
                                @endif
                                @if(isset($selectedJob->filters['employee_name']))
                                <div class="col-md-6">
                                    <strong>{{__('Employee')}}:</strong> {{$selectedJob->filters['employee_name']}}
                                </div>
                                @endif
                                @if(isset($selectedJob->filters['status']) && $selectedJob->filters['status'] !== 'all')
                                <div class="col-md-6">
                                    <strong>{{__('Status')}}:</strong> {{ucfirst($selectedJob->filters['status'])}}
                                </div>
                                @endif
                                @if(isset($selectedJob->filters['start_date']) && isset($selectedJob->filters['end_date']))
                                <div class="col-md-6">
                                    <strong>{{__('Period')}}:</strong> {{$selectedJob->filters['start_date']}} - {{$selectedJob->filters['end_date']}}
                                </div>
                                @endif
                                @if(isset($selectedJob->filters['query_string']) && $selectedJob->filters['query_string'])
                                <div class="col-12">
                                    <strong>{{__('Search Query')}}:</strong> {{$selectedJob->filters['query_string']}}
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                @if($selectedJob->error_message)
                <div class="mt-4">
                    <h6 class="text-danger">{{__('Error Details')}}</h6>
                    <div class="alert alert-danger">
                        <strong>{{__('Error')}}:</strong> {{$selectedJob->error_message}}
                        @if($selectedJob->error_details && isset($selectedJob->error_details['file']))
                        <br><small class="text-muted">
                            {{__('File')}}: {{$selectedJob->error_details['file']}}:{{$selectedJob->error_details['line']}}
                        </small>
                        @endif
                    </div>
                </div>
                @endif

                @if($selectedJob->metadata)
                <div class="mt-4">
                    <h6>{{__('Metadata')}}</h6>
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                @if(isset($selectedJob->metadata['created_by']))
                                <div class="col-md-6">
                                    <strong>{{__('Created By')}}:</strong> {{$selectedJob->metadata['created_by']}}
                                </div>
                                @endif
                                @if(isset($selectedJob->metadata['user_role']))
                                <div class="col-md-6">
                                    <strong>{{__('User Role')}}:</strong> {{ucfirst($selectedJob->metadata['user_role'])}}
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @endif
                        <div class="d-flex justify-content-end gap-2 mt-3">
                            <button type="button" class="btn btn-secondary" wire:click="closeDetailsModal">{{__('Close')}}</button>
                    @if($selectedJob->canBeDownloaded())
                    <button type="button" class="btn btn-success" wire:click="downloadFile({{$selectedJob->id}})">
                        <svg class="icon icon-xs me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        {{__('Download')}}
                    </button>
                    @endif
                    @if($selectedJob->canBeCancelled())
                    <button type="button" class="btn btn-warning" wire:click="cancelJob({{$selectedJob->id}})">
                        <svg class="icon icon-xs me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        {{__('Cancel')}}
                    </button>
                    @endif
                    @if($selectedJob->canBeDeleted())
                    <button type="button" class="btn btn-danger" wire:click="confirmDeleteJob({{$selectedJob->id}})" data-bs-toggle="modal" data-bs-target="#DeleteModal">
                        <svg class="icon icon-xs me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        {{__('Delete')}}
                    </button>
                    @endif
                </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('jobDetailsModal');
    if (modal) {
        // Listen for Livewire events
        Livewire.on('show-details-modal', () => {
            const bootstrapModal = new bootstrap.Modal(modal);
            bootstrapModal.show();
        });
        
        Livewire.on('hide-details-modal', () => {
            const bootstrapModal = bootstrap.Modal.getInstance(modal);
            if (bootstrapModal) {
                bootstrapModal.hide();
            }
        });
    }
});
</script>
