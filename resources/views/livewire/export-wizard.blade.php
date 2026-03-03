<div>
    <x-alert />

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 mb-1">{{ __('export.wizard_title') }}</h1>
            <p class="text-muted mb-0">{{ __('export.wizard_subtitle') }}</p>
        </div>
    </div>

    <div class="row">
        <!-- Left: Configuration -->
        <div class="col-md-5">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h5 class="card-title mb-4">
                        <svg class="icon icon-sm me-2 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        {{ __('export.configure') }}
                    </h5>

                    <!-- Entity Type -->
                    <div class="mb-4">
                        <label class="form-label">{{ __('export.data_type') }} <span class="text-danger">*</span></label>
                        <select wire:model.live="entitySlug" class="form-select">
                            <option value="">{{ __('export.select_entity') }}</option>
                            @foreach($availableEntities as $entity)
                            <option value="{{ $entity['slug'] }}">{{ $entity['name'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Search -->
                    @if($entitySlug)
                    <div class="mb-4">
                        <label class="form-label">{{ __('export.search_filter') }}</label>
                        <input wire:model.debounce.500ms="searchQuery" type="text" class="form-control" placeholder="{{ __('export.search_placeholder') }}">
                    </div>

                    <!-- Filters -->
                    @if(in_array($entitySlug, ['employees', 'departments', 'services']))
                    <div class="mb-4">
                        <label class="form-label">{{ __('export.filter_company') }}</label>
                        <select wire:model.live="selectedCompanyId" class="form-select">
                            <option value="">{{ __('export.all_companies') }}</option>
                            @foreach($companies as $company)
                            <option value="{{ $company['id'] }}">{{ $company['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    @if($selectedCompanyId && in_array($entitySlug, ['employees', 'services']))
                    <div class="mb-4">
                        <label class="form-label">{{ __('export.filter_department') }}</label>
                        <select wire:model.live="selectedDepartmentId" class="form-select">
                            <option value="">{{ __('export.all_departments') }}</option>
                            @foreach($departments as $dept)
                            <option value="{{ $dept['id'] }}">{{ $dept['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    <!-- Format -->
                    <div class="mb-4">
                        <label class="form-label">{{ __('export.format') }}</label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input wire:model="exportFormat" class="form-check-input" type="radio" name="format" id="fmt_xlsx" value="xlsx">
                                <label class="form-check-label" for="fmt_xlsx">Excel (.xlsx)</label>
                            </div>
                            <div class="form-check">
                                <input wire:model="exportFormat" class="form-check-input" type="radio" name="format" id="fmt_csv" value="csv">
                                <label class="form-check-label" for="fmt_csv">CSV (.csv)</label>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Export Button -->
                    @if($entitySlug)
                    <button wire:click="export" class="btn btn-primary w-100" wire:loading.attr="disabled"
                        @if(empty($selectedColumns)) disabled @endif>
                        <span wire:loading.remove wire:target="export">
                            <svg class="icon icon-xs me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            {{ __('export.download') }}
                        </span>
                        <span wire:loading wire:target="export">
                            <span class="spinner-border spinner-border-sm me-2"></span>
                            {{ __('export.generating') }}
                        </span>
                    </button>
                    @endif
                </div>
            </div>
        </div>

        <!-- Right: Column Selection -->
        <div class="col-md-7">
            @if($entitySlug && !empty($columnDefinitions))
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title mb-0">
                            <svg class="icon icon-sm me-2 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                            {{ __('export.select_columns') }}
                        </h5>
                        <div class="d-flex gap-2">
                            <button wire:click="selectAllColumns" class="btn btn-sm btn-outline-primary">{{ __('export.select_all') }}</button>
                            <button wire:click="deselectAllColumns" class="btn btn-sm btn-outline-secondary">{{ __('export.deselect_all') }}</button>
                        </div>
                    </div>

                    <p class="text-muted small mb-3">{{ __('export.columns_desc', ['count' => count($selectedColumns)]) }}</p>

                    <div class="row g-2">
                        @foreach($columnDefinitions as $colDef)
                        @php
                            $isSelected = in_array($colDef['field'], $selectedColumns);
                        @endphp
                        <div class="col-md-6">
                            <div class="form-check border rounded p-2 {{ $isSelected ? 'border-primary bg-primary bg-opacity-10' : '' }}"
                                 wire:click="toggleColumn('{{ $colDef['field'] }}')"
                                 style="cursor: pointer;">
                                <input class="form-check-input" type="checkbox" {{ $isSelected ? 'checked' : '' }}
                                       style="pointer-events: none;">
                                <label class="form-check-label" style="pointer-events: none;">
                                    {{ $colDef['label'] }}
                                </label>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @else
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-5">
                    <svg class="icon text-muted mb-3" style="width: 48px; height: 48px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <h6 class="text-muted">{{ __('export.select_type_to_see_columns') }}</h6>
                    <p class="text-muted small">{{ __('export.columns_instruction') }}</p>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
