 <div wire:ignore.self class="modal side-layout-modal fade" id="importDepartmentsModal" tabindex="-1" aria-labelledby="importdepartmentsModalLabel" aria-hidden="true"
      x-data="{ currentStep: @entangle('currentStep').live }">
     <div class="modal-dialog modal-dialog-centered modal-xl" style="max-width: 950px;" >
         <div class="modal-content">
             <div class="modal-header">
                 <h4 class="modal-title fw-bold" id="importdepartmentsModalLabel">
                     <span x-show="currentStep === 'upload'">{{__('common.import_name',['name'=>__('departments.departments')])}}</span>
                     <span x-show="currentStep === 'preview'">{{__('common.preview_data')}}</span>
                     <span x-show="currentStep === 'confirm'">{{__('common.confirm_import')}}</span>
                 </h4>
                 <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
                                    <li>{{__('common.download_sample_import_template',['name'=>__('departments.departments')])}}
                                        <a href="{{asset('templates/department_import_template.csv')}}" class="btn btn-sm btn-outline-success ms-2" download>
                                            <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                            {{__('common.download_csv_template')}}
                                        </a>
                                        <a href="{{asset('templates/import_departments.xlsx')}}" class="btn btn-sm btn-outline-secondary ms-1" download>
                                            <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                            {{__('common.download_excel_template')}}
                                        </a>
                                    </li>
                                    <li>{{__('common.fill_template_with_data',['name'=>__('departments.departments')])}}</li>
                                    <li>{{__('common.upload_filled_template')}}</li>
                                </ol>
                             </div>

                             <div class="mb-4">
                                 <label for="department_file" class="form-label">{{__('common.select_file')}}</label>
                                 <input wire:model="department_file" class="form-control @error('department_file') is-invalid @enderror" type="file" name="department_file" id="formFile" accept=".csv,.xlsx,.xls,.txt" required="">
                                 @error('department_file')
                                 <div class="invalid-feedback">{{$message}}</div>
                                 @enderror
                             </div>
                         </x-form-items.form>
                     </div>
                 </div>

                 {{-- Preview Step --}}
                 <div x-show="currentStep === 'preview'" x-transition x-transition.duration.300ms>
                     <div class="p-3 p-lg-4">
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
                         <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                             <table class="table table-striped table-hover mb-0">
                                 <thead class="table-light sticky-top">
                                     <tr>
                                         <th class="text-center" style="width: 60px;">#</th>
                                         <th class="text-center" style="width: 80px;">{{ __('common.validation_status') }}</th>
                                         @foreach($this->getPreviewColumns() as $key => $label)
                                             <th>{{ $label }}</th>
                                         @endforeach
                                         <th style="width: 200px;">{{ __('common.errors') }}/{{ __('common.warnings') }}</th>
                                     </tr>
                                 </thead>
                                 <tbody>
                                     @foreach($previewData as $row)
                                         @php
                                             $isValid = $row['validation']['valid'] ?? false;
                                             $errors = $row['validation']['errors'] ?? [];
                                             $warnings = $row['validation']['warnings'] ?? [];
                                         @endphp
                                         <tr class="{{ $isValid ? 'table-success' : 'table-danger' }}">
                                             <td class="text-center fw-bold">{{ $row['row_number'] }}</td>
                                             <td class="text-center">
                                                 @if($isValid)
                                                     <i class="fas fa-check-circle text-success" title="{{ __('common.valid') }}"></i>
                                                 @else
                                                     <i class="fas fa-times-circle text-danger" title="{{ __('common.errors') }}"></i>
                                                 @endif
                                             </td>
                                             @foreach($this->getPreviewColumns() as $key => $label)
                                                 <td>
                                                     @php
                                                         $value = $row['data'][$key] ?? '';
                                                         $parsedValue = $row['validation']['parsed_data'][$key] ?? $value;
                                                     @endphp
                                                     <span title="{{ $parsedValue !== $value ? 'Original: ' . $value : '' }}">
                                                         {{ Str::limit($parsedValue, 30) }}
                                                     </span>
                                                 </td>
                                             @endforeach
                                             <td>
                                                 @if(!empty($errors))
                                                     <div class="text-danger small">
                                                         @foreach($errors as $error)
                                                             <div>• {{ $error }}</div>
                                                         @endforeach
                                                     </div>
                                                 @endif
                                                 @if(!empty($warnings))
                                                     <div class="text-warning small">
                                                         @foreach($warnings as $warning)
                                                             <div>• {{ $warning }}</div>
                                                         @endforeach
                                                     </div>
                                                 @endif
                                                 @if(empty($errors) && empty($warnings))
                                                     <span class="text-muted small">{{ __('common.no_errors_found') }}</span>
                                                 @endif
                                             </td>
                                         </tr>
                                     @endforeach
                                 </tbody>
                             </table>
                         </div>
                     </div>
                 </div>

                 {{-- Confirm Step --}}
                 <div x-show="currentStep === 'confirm'" x-transition x-transition.duration.300ms>
                     <div class="p-3 p-lg-4">
                         <div class="text-center mb-4">
                             <i class="fas fa-check-circle text-success" style="font-size: 3rem;"></i>
                             <h4 class="mt-3">{{ __('common.ready_to_import') }}</h4>
                             <p class="text-muted">{{ __('common.confirm_import_message') }}</p>
                         </div>

                         <div class="row">
                             <div class="col-md-6">
                                 <div class="card">
                                     <div class="card-body">
                                         <h6 class="card-title">{{ __('common.import_summary') }}</h6>
                                         <ul class="list-unstyled">
                                             <li><strong>{{ __('common.total_rows') }}:</strong> {{ $totalRows }}</li>
                                             <li><strong>{{ __('common.valid_rows') }}:</strong> <span class="text-success">{{ count(array_filter($previewData, fn($row) => $row['validation']['valid'] ?? false)) }}</span></li>
                                             <li><strong>{{ __('common.rows_with_errors') }}:</strong> <span class="text-danger">{{ count($previewErrors) }}</span></li>
                                         </ul>
                                     </div>
                                 </div>
                             </div>
                             <div class="col-md-6">
                                 <div class="card">
                                     <div class="card-body">
                                         <h6 class="card-title">{{ __('common.import_settings') }}</h6>
                                         <ul class="list-unstyled">
                                             <li><strong>{{ __('common.file_name') }}:</strong> {{ $department_file ? $department_file->getClientOriginalName() : __('common.none') }}</li>
                                         </ul>
                                     </div>
                                 </div>
                             </div>
                         </div>
                     </div>
                 </div>
             </div>

             <div class="modal-footer">
                 <div x-show="currentStep === 'upload'">
                     <button class="btn btn-gray-200 text-gray-600" type="button" data-bs-dismiss="modal">{{__('common.close')}}</button>
                     <button type="button" class="btn btn-primary" wire:click="processPreview" wire:loading.attr="disabled" :disabled="!$wire.department_file || $wire.isProcessingPreview">
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

                 <div x-show="currentStep === 'preview'">
                     <button type="button" class="btn btn-secondary" wire:click="goToUpload">
                         <i class="fas fa-arrow-left me-2"></i>
                         {{__('common.back')}}
                     </button>
                     @if($this->canProceedWithImport())
                         <button type="button" class="btn btn-primary" wire:click="goToConfirm">
                             <i class="fas fa-arrow-right me-2"></i>
                             {{__('common.proceed')}}
                         </button>
                     @else
                         <button type="button" class="btn btn-warning" disabled>
                             <i class="fas fa-exclamation-triangle me-2"></i>
                             {{__('common.fix_errors_first')}}
                         </button>
                     @endif
                 </div>

                 <div x-show="currentStep === 'confirm'">
                     <button type="button" class="btn btn-secondary" wire:click="goToPreview">
                         <i class="fas fa-arrow-left me-2"></i>
                         {{__('common.back')}}
                     </button>
                     <button type="button" class="btn btn-success" wire:click="import">
                         <i class="fas fa-upload me-2"></i>
                         {{__('common.import_now')}}
                     </button>
                 </div>
             </div>
         </div>
     </div>
 </div>