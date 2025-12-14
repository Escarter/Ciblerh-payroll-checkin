<!-- Job Details Modal -->
<div wire:ignore.self class="modal side-layout-modal fade" id="detailsModal" tabindex="-1" aria-labelledby="detailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detailsModalLabel">{{__('import_jobs.job_details')}}</h5>
                <button type="button" class="btn-close" wire:click="closeDetailsModal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                @if($selectedJob)
                <div class="row g-3">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">{{__('common.general_information')}}</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <strong>{{__('common.uuid')}}:</strong> {{$selectedJob->uuid}}
                                    </div>
                                    <div class="col-md-6">
                                        <strong>{{__('common.type')}}:</strong> {{$selectedJob->import_type_display}}
                                    </div>
                                    <div class="col-md-6">
                                        <strong>{{__('common.file_name')}}:</strong> {{$selectedJob->file_name}}
                                    </div>
                                    <div class="col-md-6">
                                        <strong>{{__('common.status')}}:</strong>
                                        <span class="badge badge-lg bg-{{$selectedJob->status_badge}} ms-1">{{$selectedJob->status_display}}</span>
                                    </div>
                                    <div class="col-md-6">
                                        <strong>{{__('common.created_at')}}:</strong> {{$selectedJob->created_at->format('M d, Y H:i')}}
                                    </div>
                                    @if($selectedJob->started_at)
                                    <div class="col-md-6">
                                        <strong>{{__('common.started_at')}}:</strong> {{$selectedJob->started_at->format('M d, Y H:i')}}
                                    </div>
                                    @endif
                                    @if($selectedJob->completed_at)
                                    <div class="col-md-6">
                                        <strong>{{__('common.completed_at')}}:</strong> {{$selectedJob->completed_at->format('M d, Y H:i')}}
                                    </div>
                                    @endif
                                    @if($selectedJob->duration)
                                    <div class="col-md-6">
                                        <strong>{{__('common.duration')}}:</strong> {{$selectedJob->duration}}
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">{{__('import_jobs.progress_statistics')}}</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="text-center">
                                            <div class="h4 text-primary">{{$selectedJob->total_rows}}</div>
                                            <small class="text-muted">{{__('common.total_rows')}}</small>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="text-center">
                                            <div class="h4 text-success">{{$selectedJob->successful_imports}}</div>
                                            <small class="text-muted">{{__('common.successful')}}</small>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="text-center">
                                            <div class="h4 text-danger">{{$selectedJob->failed_imports}}</div>
                                            <small class="text-muted">{{__('common.failed')}}</small>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="text-center">
                                            <div class="h4 text-info">{{$selectedJob->processed_rows}}</div>
                                            <small class="text-muted">{{__('common.processed')}}</small>
                                        </div>
                                    </div>
                                </div>

                                @if($selectedJob->status === 'processing')
                                <div class="mt-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <small>{{__('common.progress')}}</small>
                                        <small>{{$selectedJob->progress_percentage}}%</small>
                                    </div>
                                    <div class="progress">
                                        <div class="progress-bar" role="progressbar" style="width: {{$selectedJob->progress_percentage}}%"></div>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if($selectedJob->error_message)
                    <div class="col-12">
                        <div class="card border-danger">
                            <div class="card-header bg-danger text-white">
                                <h6 class="mb-0">{{__('common.error_message')}}</h6>
                            </div>
                            <div class="card-body">
                                <p class="text-danger mb-0">{{$selectedJob->error_message}}</p>
                            </div>
                        </div>
                    </div>
                    @endif

                    @if($selectedJob->error_details)
                    <div class="col-12">
                        <div class="card border-warning">
                            <div class="card-header bg-warning">
                                <h6 class="mb-0">{{__('common.error_details')}}</h6>
                            </div>
                            <div class="card-body">
                                <pre class="text-muted small">{{json_encode($selectedJob->error_details, JSON_PRETTY_PRINT)}}</pre>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" wire:click="closeDetailsModal">{{__('common.close')}}</button>
            </div>
        </div>
    </div>
</div>