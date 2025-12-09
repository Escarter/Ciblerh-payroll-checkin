@props([
    'previewData' => [],
    'previewErrors' => [],
    'totalRows' => 0,
    'processedRows' => 0,
    'hasLargeFile' => false,
    'showPreview' => false,
    'columns' => [],
    'canProceed' => true,
    'modalId' => 'importPreviewModal',
    'title' => __('common.preview_data'),
    'importType' => 'generic'
])

<div wire:ignore.self class="modal side-layout-modal fade" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalId }}Label" aria-hidden="true"
     x-data="{ show: @entangle('showPreview').live }">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="{{ $modalId }}Label">
                    <i class="fas fa-eye me-2"></i>{{ $title }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('common.close') }}"
                        wire:click="closePreview"></button>
            </div>

            <div class="modal-body p-0">
                <!-- Preview Stats -->
                <div class="p-3 border-bottom bg-light">
                    @php
                        $stats = [
                            'total_preview' => count($previewData),
                            'valid' => count(array_filter($previewData, fn($row) => $row['validation']['valid'] ?? false)),
                            'errors' => count($previewErrors)
                        ];
                    @endphp

                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <div class="card h-100 border-0 bg-primary bg-opacity-10">
                                <div class="card-body text-center p-2">
                                    <div class="h4 mb-1 text-primary">{{ $stats['total_preview'] }}</div>
                                    <small class="text-muted">{{ __('common.row_number') }} ({{ __('common.preview_data') }})</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card h-100 border-0 bg-success bg-opacity-10">
                                <div class="card-body text-center p-2">
                                    <div class="h4 mb-1 text-success">{{ $stats['valid'] }}</div>
                                    <small class="text-muted">{{ __('common.valid') }}</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card h-100 border-0 bg-danger bg-opacity-10">
                                <div class="card-body text-center p-2">
                                    <div class="h4 mb-1 text-danger">{{ $stats['errors'] }}</div>
                                    <small class="text-muted">{{ __('common.errors') }}</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card h-100 border-0 bg-info bg-opacity-10">
                                <div class="card-body text-center p-2">
                                    <div class="h4 mb-1 text-info">{{ $totalRows }}</div>
                                    <small class="text-muted">{{ __('common.row_number') }} ({{ __('common.all') }})</small>
                                </div>
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

                <!-- Preview Table -->
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th class="text-center" style="width: 60px;">#</th>
                                <th class="text-center" style="width: 80px;">{{ __('common.validation_status') }}</th>
                                @foreach($columns as $key => $label)
                                    <th>{{ $label }}</th>
                                @endforeach
                                <th style="min-width: 200px;">{{ __('common.errors') }}/{{ __('common.warnings') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($previewData as $row)
                                @php
                                    $isValid = $row['validation']['valid'] ?? false;
                                    $errors = $row['validation']['errors'] ?? [];
                                    $warnings = $row['validation']['warnings'] ?? [];
                                @endphp
                                <tr class="{{ $isValid ? 'table-success' : 'table-danger' }} {{ !empty($errors) ? 'border-danger' : '' }}">
                                    <td class="text-center fw-bold">{{ $row['row_number'] }}</td>
                                    <td class="text-center">
                                        @if($isValid)
                                            <i class="fas fa-check-circle text-success fs-5" title="{{ __('common.valid') }}"></i>
                                        @else
                                            <i class="fas fa-times-circle text-danger fs-5" title="{{ __('common.errors') }}"></i>
                                        @endif
                                    </td>
                                    @foreach($columns as $key => $label)
                                        <td>
                                            @php
                                                $value = $row['data'][$key] ?? '';
                                                $parsedValue = $row['validation']['parsed_data'][$key] ?? $value;
                                            @endphp
                                            <div class="d-flex align-items-center">
                                                <span class="{{ $parsedValue !== $value ? 'text-primary fw-bold' : '' }}"
                                                      title="{{ $parsedValue !== $value ? __('Original: :value', ['value' => $value]) : '' }}">
                                                    {{ Str::limit($parsedValue, 30) }}
                                                </span>
                                                @if($parsedValue !== $value)
                                                    <i class="fas fa-info-circle text-primary ms-1"
                                                       title="{{ __('Original: :value', ['value' => $value]) }}"></i>
                                                @endif
                                            </div>
                                        </td>
                                    @endforeach
                                    <td>
                                        @if(!empty($errors))
                                            <div class="text-danger small mb-1">
                                                <strong>{{ __('common.errors') }}:</strong>
                                                @foreach($errors as $error)
                                                    <div class="ms-2">• {{ $error }}</div>
                                                @endforeach
                                            </div>
                                        @endif
                                        @if(!empty($warnings))
                                            <div class="text-warning small">
                                                <strong>{{ __('common.warnings') }}:</strong>
                                                @foreach($warnings as $warning)
                                                    <div class="ms-2">• {{ $warning }}</div>
                                                @endforeach
                                            </div>
                                        @endif
                                        @if(empty($errors) && empty($warnings))
                                            <span class="text-success small">
                                                <i class="fas fa-check me-1"></i>{{ __('common.no_errors_found') }}
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ count($columns) + 3 }}" class="text-center text-muted py-4">
                                        <i class="fas fa-inbox fa-2x mb-2"></i>
                                        <div>{{ __('common.no_preview_data') }}</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"
                        wire:click="closePreview">
                    <i class="fas fa-times me-2"></i>
                    {{ __('common.close_preview') }}
                </button>

                @if($canProceed)
                    <button type="button" class="btn btn-success" wire:click="$dispatch('proceed-with-import-{{ $importType }}')">
                        <i class="fas fa-upload me-2"></i>
                        {{ __('common.proceed_with_import') }}
                    </button>
                @else
                    <button type="button" class="btn btn-warning" disabled title="{{ __('common.fix_errors_first') }}">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        {{ __('common.fix_errors_first') }}
                    </button>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('livewire:initialized', () => {
    Livewire.on('proceed-with-import-{{ $importType }}', () => {
        // Close modal first
        const modal = document.getElementById('{{ $modalId }}');
        if (modal) {
            const bsModal = bootstrap.Modal.getInstance(modal);
            if (bsModal) {
                bsModal.hide();
            }
        }

        // Then proceed with import
        $wire.import();
    });
});
</script>