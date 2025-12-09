 <div wire:ignore.self class="modal side-layout-modal fade" id="importTypesModal" tabindex="-1" aria-labelledby="importTypesModalLabel" aria-hidden="true"
      x-data="{ currentStep: @entangle('currentStep').live }">
     <div class="modal-dialog modal-dialog-centered modal-xl" style="max-width: 950px;" >
         <div class="modal-content">
             <div class="modal-header">
                 <h4 class="modal-title fw-bold" id="importTypesModalLabel">
                     <span x-show="currentStep === 'upload'">{{__('common.import_name',['name'=>__('leaves.leave_types')])}}</span>
                     <span x-show="currentStep === 'preview'">{{__('common.preview_data')}}</span>
                     <span x-show="currentStep === 'confirm'">{{__('common.confirm_import')}}</span>
                 </h4>
                 <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" wire:click="closePreview"></button>
             </div>

             {{-- Progress Indicator --}}
             <div class="progress-steps px-3 pt-2 mt-3">
                 <div class="d-flex justify-content-between">
                     <div class="step" :class="{ 'active': currentStep === 'upload', 'completed': currentStep === 'preview' || currentStep === 'confirm' }">
                         <span class="step-number">1</span>
                         <span class="step-label">{{__('common.upload_file')}}</span>
                     </div>
                     <div class="step" :class="{ 'active': currentStep === 'preview', 'completed': currentStep === 'confirm' }">
                         <span class="step-number">2</span>
                         <span class="step-label">{{__('common.preview_data')}}</span>
                     </div>
                     <div class="step" :class="{ 'active': currentStep === 'confirm' }">
                         <span class="step-number">3</span>
                         <span class="step-label">{{__('common.confirm_import')}}</span>
                     </div>
                 </div>
             </div>

             <div class="modal-body">
                 {{-- Upload Step --}}
                 <div x-show="currentStep === 'upload'" x-transition x-transition.duration.300ms>
                     <div class="px-3 pt-1 pb-3">
                         <x-form-items.form class="form-modal">
                         <div class='mb-4'>
                            <ol>
                                    <li>{{__('common.download_sample_import_template',['name'=>__('leaves.leave_types')])}}
                                        <a href="{{asset('templates/leave_type_import_template.csv')}}" class="btn btn-sm btn-outline-success ms-2" download>
                                        <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                            {{__('common.download_csv_template')}}
                                    </a>
                                </li>
                                <li>{{__('common.fill_template_with_data',['name'=>__('leaves.leave_types')])}}</li>
                                <li>{{__('common.upload_filled_template')}}</li>
                            </ol>
                         </div>

                             <div class="mb-4">
                                 <label for="leave_type_file" class="form-label">{{__('common.select_file')}}</label>
                                 <input wire:model="leave_type_file" class="form-control @error('leave_type_file') is-invalid @enderror" type="file" name="leave_type_file" id="formFile" accept=".csv,.xlsx,.xls,.txt" required="">
                                 <div class="form-text">
                                     {{__('common.max_file_size_info', ['size' => '5MB'])}}
                                 </div>
                                 @error('leave_type_file')
                                 <div class="invalid-feedback">{{$message}}</div>
                                 @enderror
                             </div>
                         </x-form-items.form>
                     </div>
                 </div>

                 {{-- Preview Step --}}
                 <div x-show="currentStep === 'preview'" x-transition x-transition.duration.300ms>
                     <div class="p-3 p-lg-4">
                         {{-- File Analysis Warning --}}
                         @if(!empty($fileAnalysisWarning))
                             <div class="alert alert-warning mb-3">
                                 <i class="fas fa-exclamation-triangle me-2"></i>
                                 {{ $fileAnalysisWarning }}
                             </div>
                         @endif

                         {{-- Preview Stats --}}
                         <div class="mb-4">
                             @php
                                 $stats = [
                                     'total_preview' => count($previewData),
                                     'valid' => count(array_filter($previewData, fn($row) => $row['validation']['valid'] ?? false)),
                                     'errors' => count($previewErrors)
                                 ];
                             @endphp

                             <div class="row g-3 mb-3">
                                 <div class="col-md-3">
                                     <div class="text-center">
                                         <div class="h4 mb-0">{{ $stats['total_preview'] }}</div>
                                         <small class="text-muted">{{ __('common.row_number') }} ({{ __('common.preview_data') }})</small>
                                     </div>
                                 </div>
                                 <div class="col-md-3">
                                     <div class="text-center">
                                         <div class="h4 mb-0 text-success">{{ $stats['valid'] }}</div>
                                         <small class="text-muted">{{ __('common.valid') }}</small>
                                     </div>
                                 </div>
                                 <div class="col-md-3">
                                     <div class="text-center">
                                         <div class="h4 mb-0 text-danger">{{ $stats['errors'] }}</div>
                                         <small class="text-muted">{{ __('common.errors') }}</small>
                                     </div>
                                 </div>
                                 <div class="col-md-3">
                                     <div class="text-center">
                                         <div class="h4 mb-0 text-info">{{ $totalRows }}</div>
                                         <small class="text-muted">{{ __('common.row_number') }} ({{ __('common.all') }})</small>
                                     </div>
                                 </div>
                             </div>

                             @if($hasLargeFile)
                                 <div class="alert alert-warning mb-3">
                                     <i class="fas fa-exclamation-triangle me-2"></i>
                                     {{ __('common.preview_large_file_warning', [
                                         'total' => $totalRows,
                                         'shown' => $processedRows
                                     ]) }}
                                 </div>
                             @endif

                             @if($stats['errors'] === 0)
                                 <div class="alert alert-success mb-3">
                                     <i class="fas fa-check-circle me-2"></i>
                                     {{ __('common.preview_all_valid') }}
                                 </div>
                             @else
                                 <div class="alert alert-danger mb-3">
                                     <i class="fas fa-exclamation-circle me-2"></i>
                                     {{ __('common.preview_validation_summary', [
                                         'valid' => $stats['valid'],
                                         'errors' => $stats['errors'],
                                         'total' => $stats['total_preview']
                                     ]) }}
                                 </div>
                             @endif
                         </div>

                         {{-- Preview Table --}}
                         <div class="table-responsive">
                             <table class="table table-sm table-bordered">
                                 <thead class="table-light">
                                     <tr>
                                         <th class="text-center">#</th>
                                         @foreach($this->getPreviewColumns() as $column)
                                             <th>{{ $column }}</th>
                                         @endforeach
                                         <th>{{ __('common.status') }}</th>
                                     </tr>
                                 </thead>
                                 <tbody>
                                     @forelse($previewData as $row)
                                     <tr>
                                         <td class="text-center">{{ $row['row_number'] }}</td>
                                         @foreach($row['data'] as $cell)
                                             <td>{{ $cell }}</td>
                                         @endforeach
                                         <td>
                                             @if($row['validation']['valid'])
                                                 <span class="badge bg-success">{{ __('common.valid') }}</span>
                                             @else
                                                 <span class="badge bg-danger">{{ __('common.invalid') }}</span>
                                             @endif
                                         </td>
                                     </tr>
                                     @empty
                                     <tr>
                                         <td colspan="{{ count($this->getPreviewColumns()) + 2 }}" class="text-center text-muted">
                                             {{ __('common.no_data_to_preview') }}
                                         </td>
                                     </tr>
                                     @endforelse
                                 </tbody>
                             </table>
                         </div>

                         {{-- Validation Errors --}}
                         @if(count($previewErrors) > 0)
                         <div class="mt-3">
                             <h6 class="text-danger mb-2">{{ __('common.validation_errors') }}:</h6>
                             <div class="list-group">
                                 @foreach($previewErrors as $error)
                                 <div class="list-group-item list-group-item-danger">
                                     <strong>{{ __('common.row') }} {{ $error['row'] }}:</strong>
                                     <ul class="mb-0 mt-1">
                                         @foreach($error['errors'] as $message)
                                         <li>{{ $message }}</li>
                                         @endforeach
                                     </ul>
                                 </div>
                                 @endforeach
                             </div>
                         </div>
                         @endif
                     </div>
                 </div>

                 {{-- Confirm Step --}}
                 <div x-show="currentStep === 'confirm'" x-transition x-transition.duration.300ms>
                     <div class="p-3 p-lg-4 text-center">
                         <div class="mb-4">
                             <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                             <h4 class="mt-3">{{ __('common.ready_to_queue_import') }}</h4>
                             <p class="text-muted">{{ __('common.confirm_queue_message') }}</p>
                         </div>

                         <div class="row justify-content-center">
                             <div class="col-md-4">
                                 <div class="card border-success">
                                     <div class="card-body text-center">
                                         <div class="h3 text-success">{{ count(array_filter($previewData, fn($row) => $row['validation']['valid'] ?? false)) }}</div>
                                         <small class="text-muted">{{ __('common.valid_rows') }}</small>
                                     </div>
                                 </div>
                             </div>
                             <div class="col-md-4">
                                 <div class="card border-danger">
                                     <div class="card-body text-center">
                                         <div class="h3 text-danger">{{ count($previewErrors) }}</div>
                                         <small class="text-muted">{{ __('common.rows_with_errors') }}</small>
                                     </div>
                                 </div>
                             </div>
                         </div>
                     </div>
                 </div>
             </div>

             {{-- Modal Footer --}}
             <div class="modal-footer">
                 <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal" wire:click="closePreview">{{__('common.close')}}</button>
                 <a href="{{route('portal.import-results.index')}}" class="btn btn-outline-info" wire:navigate>{{__('common.view_import_history')}}</a>

                 {{-- Upload Step Actions --}}
                 <div x-show="currentStep === 'upload'">
                     <button class="btn btn-primary" type="button" wire:click="processPreview" wire:loading.attr="disabled" :disabled="!$wire.leave_type_file || $wire.isProcessingPreview">
                         <span wire:loading.remove x-show="!$wire.isProcessingPreview">
                             <i class="fas fa-eye me-2"></i>{{__('common.preview_data')}}
                         </span>
                         <span wire:loading x-show="!$wire.isProcessingPreview">
                             <i class="fas fa-spinner fa-spin me-2"></i>{{__('common.processing')}}...
                         </span>
                         <span x-show="$wire.isProcessingPreview">
                             <i class="fas fa-spinner fa-spin me-2"></i>
                             <span x-text="$wire.processingStep"></span>
                             <span x-show="$wire.processingProgress > 0"> (<span x-text="$wire.processingProgress"></span>%)</span>
                         </span>
                     </button>

                     {{-- Processing Progress Bar --}}
                     <div x-show="$wire.isProcessingPreview" class="mt-2">
                         <div class="progress" style="height: 6px;">
                             <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary"
                                  role="progressbar"
                                  :style="`width: ${$wire.processingProgress}%`"
                                  :aria-valuenow="$wire.processingProgress"
                                  aria-valuemin="0"
                                  aria-valuemax="100"></div>
                         </div>
                     </div>
                 </div>

                 {{-- Preview Step Actions --}}
                 <div x-show="currentStep === 'preview'">
                     <button class="btn btn-outline-primary me-2" type="button" wire:click="$set('currentStep', 'upload')">
                         <i class="fas fa-arrow-left me-1"></i>{{__('common.back')}}
                     </button>
                     <button class="btn btn-success" type="button" wire:click="$set('currentStep', 'confirm')" :disabled="$wire.previewErrors.length > 0">
                         <i class="fas fa-check me-1"></i>{{__('common.proceed')}}
                     </button>
                 </div>

                 {{-- Confirm Step Actions --}}
                 <div x-show="currentStep === 'confirm'">
                     <button class="btn btn-outline-primary me-2" type="button" wire:click="$set('currentStep', 'preview')">
                         <i class="fas fa-arrow-left me-1"></i>{{__('common.back')}}
                     </button>
                     <button class="btn btn-success" type="button" wire:click="import" wire:loading.attr="disabled">
                         <span wire:loading.remove><i class="fas fa-upload me-1"></i>{{__('common.queue_import')}}</span>
                         <span wire:loading><i class="fas fa-spinner fa-spin me-1"></i>{{__('common.queuing')}}...</span>
                     </button>
                 </div>
             </div>
         </div>
     </div>
 </div>