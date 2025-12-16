<!-- Create Import Modal -->
<div wire:ignore.self class="modal side-layout-modal fade" id="createImportModal" tabindex="-1" aria-labelledby="modal-form" data-bs-backdrop="static" data-bs-keyboard="false" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 800px;">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title fw-bold" id="modal-form">
                    @if($currentStep === 'upload')
                    {{__('import_jobs.create_new_import')}}
                    @elseif($currentStep === 'preview')
                    {{__('common.preview_data')}}
                    @elseif($currentStep === 'confirm')
                    {{__('common.confirm_import')}}
                    @endif
                </h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" wire:click="closeCreateModal" aria-label="Close"></button>
            </div>

            {{-- Progress Indicator --}}
            <div class="progress-steps px-3 pt-2 mt-3">
                <div class="d-flex justify-content-between">
                    <div class="step {{ $currentStep === 'upload' ? 'active' : ($currentStep === 'preview' || $currentStep === 'confirm' ? 'completed' : '') }}">
                        <span class="step-number">1</span>
                        <span class="step-label">{{__('import_jobs.create_import')}}</span>
                    </div>
                    <div class="step {{ $currentStep === 'preview' ? 'active' : ($currentStep === 'confirm' ? 'completed' : '') }}">
                        <span class="step-number">2</span>
                        <span class="step-label">{{__('common.preview_data')}}</span>
                    </div>
                    <div class="step {{ $currentStep === 'confirm' ? 'active' : '' }}">
                        <span class="step-number">3</span>
                        <span class="step-label">{{__('common.confirm_import')}}</span>
                    </div>
                </div>
            </div>

            <div class="modal-body">
                {{-- Upload Step --}}
                @if($currentStep === 'upload')
                <div x-transition x-transition.duration.300ms>
                    <div class="px-3 pt-1 pb-3">
                        <form id="create-import-form" wire:submit.prevent="createNewImport">
                            <div class="mb-4">
                                <label for="importType" class="form-label">{{__('common.import_type')}} <span class="text-danger">*</span></label>
                                <select wire:model.live="newImport.import_type" class="form-select @error('newImport.import_type') is-invalid @enderror" id="importType" required>
                                    <option value="">{{__('common.select_import_type')}}</option>
                                    @foreach($availableImportTypes as $type => $config)
                                    <option value="{{$type}}">{{$config['label']}}</option>
                                    @endforeach
                                </select>
                                @error('newImport.import_type')
                                <div class="invalid-feedback">{{$message}}</div>
                                @enderror
                            </div>

                            @php
                            $importTypeConfig = ($newImport['import_type'] ?? false) ? ($availableImportTypes[$newImport['import_type']] ?? null) : null;
                            @endphp

                            {{-- Template download section --}}
                            @if($importTypeConfig)
                            <div class="mb-4">
                                <label class="form-label">{{__('common.download_template')}}</label>
                                <p class="text-muted small mb-3">{{__('import_jobs.download_template_description')}}</p>
                                <div class="d-flex gap-2 flex-wrap">
                                    @if($importTypeConfig['template'] ?? false)
                                    <a href="{{route('portal.download-template', $importTypeConfig['template'])}}" class="btn btn-sm btn-outline-success">
                                        <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        {{__('common.download_csv_template')}}
                                    </a>
                                    @endif
                                    @if($importTypeConfig['excel_template'] ?? false)
                                    <a href="{{route('portal.download-template', $importTypeConfig['excel_template'])}}" class="btn btn-sm btn-outline-secondary">
                                        <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        {{__('common.download_excel_template')}}
                                    </a>
                                    @endif
                                </div>
                            </div>

                            {{-- Import type description --}}
                            @if($importTypeConfig['description'] ?? false)
                            <div class="alert alert-info">
                                <strong>{{__('common.description')}}:</strong> {{$importTypeConfig['description']}}
                            </div>
                            @endif
                            @endif

                            {{-- Dynamic fields based on import type --}}
                            @if($newImport['import_type'] ?? false)
                            @if($importTypeConfig && !empty($importTypeConfig['fields']))
                            @foreach($importTypeConfig['fields'] as $fieldName => $fieldConfig)
                            <div class="mb-4">
                                @if($fieldConfig['type'] === 'select')
                                <label class="form-label">
                                    {{$fieldConfig['label']}}
                                    @if($fieldConfig['required']) <span class="text-danger">*</span> @endif
                                </label>
                                <select wire:model.live="newImport.{{$fieldName}}" class="form-select @error(" newImport.{$fieldName}") is-invalid @enderror"
                                    @if($fieldConfig['depends_on'] ?? false) wire:loading.attr="disabled" @endif>
                                    <option value="">{{__('common.select')}} {{$fieldConfig['label']}}</option>
                                    @if($fieldConfig['options'] === 'companies')
                                    @foreach($companies as $company)
                                    <option value="{{$company->id}}">{{$company->name}}</option>
                                    @endforeach
                                    @elseif($fieldConfig['options'] === 'departments')
                                    @foreach($departments as $department)
                                    <option value="{{$department->id}}">{{$department->name}} ({{$department->company->name}})</option>
                                    @endforeach
                                    @elseif($fieldConfig['options'] === 'services')
                                    @foreach($services as $service)
                                    <option value="{{$service->id}}">{{$service->name}} ({{$service->department->name}} - {{$service->company->name}})</option>
                                    @endforeach
                                    @endif
                                </select>
                                @error("newImport.{$fieldName}")
                                <div class="invalid-feedback">{{$message}}</div>
                                @enderror
                                @elseif($fieldConfig['type'] === 'checkbox')
                                <div class="form-check">
                                    <input wire:model="newImport.{{$fieldName}}" class="form-check-input" type="checkbox"
                                        id="field-{{$fieldName}}">
                                    <label class="form-check-label" for="field-{{$fieldName}}">
                                        {{$fieldConfig['label']}}
                                    </label>
                                    @if(isset($fieldConfig['description']))
                                    <small class="text-muted d-block mt-1">{{ $fieldConfig['description'] }}</small>
                                    @endif
                                </div>
                                @endif
                            </div>
                            @endforeach
                            @endif
                            @endif

                            {{-- File upload field - only shown when import type is selected --}}
                            @if($newImport['import_type'] ?? false)
                            <div class="mb-4">
                                <label for="importFile" class="form-label">{{__('common.file')}} <span class="text-danger">*</span></label>
                                <input wire:model="newImport.file" type="file" class="form-control @error('newImport.file') is-invalid @enderror" id="importFile"
                                    accept=".xlsx,.xls,.csv,.txt" required>
                                @error('newImport.file')
                                <div class="invalid-feedback">{{$message}}</div>
                                @enderror
                                <small class="text-muted">{{__('common.accepted_formats')}}: XLSX, XLS, CSV, TXT ({{__('common.max')}} {{$maxFileSize}}MB)</small>
                            </div>
                            @endif
                        </form>
                    </div>

                    {{-- Upload Step Actions --}}
                    <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                        <div>
                            <small class="text-muted">{{__('common.step')}} 1 {{__('common.of')}} 3: {{__('import_jobs.create_import')}}</small>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary" wire:click="closeCreateModal">{{__('common.cancel')}}</button>
                            <button type="submit" form="create-import-form" class="btn btn-primary" wire:loading.attr="disabled">
                                <span wire:loading.remove>{{__('common.preview_data')}}</span>
                                <span wire:loading>
                                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                    {{__('common.processing')}}...
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Preview Step --}}
                @if($currentStep === 'preview')
                <div x-transition x-transition.duration.300ms>
                    <div class="p-3 p-lg-4">
                        {{-- Processing Indicator --}}
                        @if($isProcessingPreview)
                        <div class="text-center mb-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">{{__('common.loading')}}...</span>
                            </div>
                            <p class="mt-2 text-muted">
                                <span>{{ $processingStep }}</span>
                                @if($processingProgress > 0)
                                <span> ({{ $processingProgress }}%)</span>
                                @endif
                            </p>
                            <div class="progress mt-2" style="height: 6px;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary"
                                    role="progressbar"
                                    style="width: {{ $processingProgress }}%"
                                    aria-valuenow="{{ $processingProgress }}"
                                    aria-valuemin="0"
                                    aria-valuemax="100"></div>
                            </div>
                        </div>
                        @else
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
                                        <small class="text-muted">{{ __('common.rows_previewed') }}</small>
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
                                        <small class="text-muted">{{ __('common.total_rows') }}</small>
                                    </div>
                                </div>
                            </div>

                            @if($hasLargeFile)
                            <div class="alert alert-warning mb-3">
                                {{ __('common.preview_large_file_warning', [
                                                'total' => $totalRows,
                                                'shown' => $processedRows
                                            ]) }}
                            </div>
                            @endif

                            @if($stats['errors'] === 0)
                            <div class="alert alert-success mb-3">
                                {{ __('common.preview_all_valid') }}
                            </div>
                            @else
                            <div class="alert alert-danger mb-3">
                                {{ __('common.preview_validation_summary', [
                                                'valid' => $stats['valid'],
                                                'errors' => $stats['errors'],
                                                'total' => $stats['total_preview']
                                            ]) }}
                            </div>
                            @endif

                            {{-- Preview Table --}}
                            @if(count($previewData) > 0)
                            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                <table class="table table-striped table-hover mb-0">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th class="text-center" style="width: 60px;">#</th>
                                            <th class="text-center" style="width: 80px;">{{ __('common.status') }}</th>
                                            @foreach($this->getPreviewColumns() as $key => $label)
                                            <th>{{ $label }}</th>
                                            @endforeach
                                            <th style="width: 200px;">{{ __('common.errors') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($previewData as $row)
                                        @php
                                        $isValid = $row['validation']['valid'] ?? false;
                                        $errors = $row['validation']['errors'] ?? [];
                                        @endphp
                                        <tr class="{{ $isValid ? 'table-success' : 'table-danger' }}">
                                            <td class="text-center fw-bold">{{ $row['row_number'] }}</td>
                                            <td class="text-center">
                                                @if($isValid)
                                                <span class="badge bg-success">{{ __('common.valid') }}</span>
                                                @else
                                                <span class="badge bg-danger">{{ __('common.error') }}</span>
                                                @endif
                                            </td>
                                            @foreach($this->getPreviewColumns() as $key => $label)
                                            <td>
                                                @php
                                                $value = $row['data'][$key] ?? '';
                                                $parsedValue = $row['validation']['parsed_data'][$key] ?? $value;
                                                @endphp
                                                <span title="{{ $parsedValue !== $value ? __('common.original') . ': ' . $value : '' }}">
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
                                                @else
                                                <span class="text-muted small">{{ __('common.no_errors_found') }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @else
                            {{-- No Preview Data Fallback --}}
                            <div class="text-center py-5">
                                <h6 class="text-muted">{{__('import_jobs.no_preview_data_available')}}</h6>
                                <p class="text-muted small">{{__('import_jobs.preview_data_unavailable_message')}}</p>
                                <button wire:click="processPreview" class="btn btn-sm btn-primary mt-2">
                                    {{__('common.process_preview')}}
                                </button>
                            </div>
                            @endif
                        </div>
                        @endif

                        {{-- Preview Step Actions --}}
                        <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                            <div>
                                <small class="text-muted">{{__('common.step')}} 2 {{__('common.of')}} 3: {{__('common.preview_data')}}</small>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-secondary" wire:click="goBackToUpload">
                                    {{__('common.back')}}
                                </button>
                                <button type="button" class="btn btn-primary" wire:click="goToConfirm" @if(count($previewData)===0) disabled @endif>
                                    {{__('common.confirm_import')}}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Confirm Step --}}
                @if($currentStep === 'confirm')
                <div x-transition x-transition.duration.300ms>
                    <div class="p-3 p-lg-4">
                        {{-- Confirmation Content --}}
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
                                            @if($previewSkipped)
                                            <li><strong>{{ __('common.estimated_rows') }}:</strong> {{ $totalRows }} @if($isRowCountEstimated)<small class="text-muted">({{ __('common.estimated') }})</small>@endif</li>
                                            <li><strong>{{ __('common.status') }}:</strong> <span class="text-warning">{{ __('common.large_file_ready') }}</span></li>
                                            @if($fileAnalysisWarning)
                                            <li><strong>{{ __('common.note') }}:</strong> <span class="text-info">{{ $fileAnalysisWarning }}</span></li>
                                            @endif
                                            @else
                                            <li><strong>{{ __('common.total_rows') }}:</strong> {{ $totalRows }}</li>
                                            <li><strong>{{ __('common.valid_rows') }}:</strong> <span class="text-success">{{ count(array_filter($previewData, fn($row) => $row['validation']['valid'] ?? false)) }}</span></li>
                                            <li><strong>{{ __('common.rows_with_errors') }}:</strong> <span class="text-danger">{{ count($previewErrors) }}</span></li>
                                            @endif
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-body">
                                        <h6 class="card-title">{{ __('common.import_settings') }}</h6>
                                        <ul class="list-unstyled">
                                            <li><strong>{{ __('common.import_type') }}:</strong> {{ $availableImportTypes[$newImport['import_type']]['label'] ?? 'N/A' }}</li>
                                            <li><strong>{{ __('common.file_name') }}:</strong> {{ basename($filePath ?? '') }}</li>
                                            @if($newImport['company_id'] ?? false)
                                            <li><strong>{{ __('companies.company') }}:</strong> {{ $companies->find($newImport['company_id'])->name ?? 'N/A' }}</li>
                                            @endif
                                            @if($newImport['auto_create_entities'] ?? false)
                                            <li><strong>{{ __('import_types.auto_create_entities') }}:</strong> {{ __('common.yes') }}</li>
                                            @endif
                                            @if($newImport['send_welcome_emails'] ?? false)
                                            <li><strong>{{ __('common.send_welcome_emails') }}:</strong> {{ __('common.yes') }}</li>
                                            @endif
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Confirm Step Actions --}}
                        <div class="d-flex justify-content-between align-items-center mt-4 pt-4 border-top">
                            <div>
                                <small class="text-muted">{{__('common.step')}} 3 {{__('common.of')}} 3: {{__('common.confirm_import')}}</small>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-secondary" wire:click="goToPreview" @if($previewSkipped) disabled @endif>
                                    {{__('common.back')}}
                                </button>
                                <button type="button" class="btn btn-success" wire:click="confirmImport" wire:loading.attr="disabled">
                                    <span wire:loading.remove>
                                        {{__('common.start_import')}}
                                    </span>
                                    <span wire:loading>
                                        <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                        {{__('common.starting_import')}}...
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('createImportModal');
        if (modal) {
            // Listen for Livewire events
            Livewire.on('show-create-modal', () => {
                const bootstrapModal = new bootstrap.Modal(modal);
                bootstrapModal.show();
            });

            Livewire.on('hide-create-modal', () => {
                const bootstrapModal = bootstrap.Modal.getInstance(modal);
                if (bootstrapModal) {
                    bootstrapModal.hide();
                }
            });
        }
    });
</script>