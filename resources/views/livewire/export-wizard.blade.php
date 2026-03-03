<div>
    <x-alert />

    {{-- Scoped Styles --}}
    <style>
        .export-wizard .entity-card {
            border: 2px solid #e2e8f0;
            border-radius: .75rem;
            padding: .65rem .5rem;
            cursor: pointer;
            transition: all .2s ease;
            text-align: center;
        }
        .export-wizard .entity-card:hover { border-color: #b5c0d0; transform: translateY(-1px); }
        .export-wizard .entity-card.selected { border-color: #262b40; background: #f0f1f5; }

        .export-wizard .format-card {
            border: 2px solid #e2e8f0;
            border-radius: .75rem;
            padding: .75rem 1rem;
            cursor: pointer;
            transition: all .2s ease;
            position: relative;
            flex: 1;
        }
        .export-wizard .format-card:hover { border-color: #b5c0d0; background: #f8f9fc; }
        .export-wizard .format-card.selected { border-color: #262b40; background: #f0f1f5; }

        .export-wizard .col-chip {
            border: 2px solid #e2e8f0;
            border-radius: .625rem;
            padding: .5rem .75rem;
            cursor: pointer;
            transition: all .15s ease;
            display: flex;
            align-items: center;
            gap: .5rem;
        }
        .export-wizard .col-chip:hover { border-color: #b5c0d0; background: #f8f9fc; }
        .export-wizard .col-chip.selected { border-color: #262b40; background: #f0f1f5; }
        .export-wizard .col-chip .chip-check {
            width: 18px; height: 18px; min-width: 18px;
            border-radius: 4px;
            border: 2px solid #cbd5e0;
            display: flex; align-items: center; justify-content: center;
            transition: all .15s ease;
        }
        .export-wizard .col-chip.selected .chip-check {
            background: #262b40; border-color: #262b40;
        }
    </style>

    <div class="export-wizard">

    {{-- Page Header --}}
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
        <div>
            <h1 class="h4 fw-bold mb-1">{{ __('export.wizard_title') }}</h1>
            <p class="text-muted mb-0 small">{{ __('export.wizard_subtitle') }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('portal.import-wizard') }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
                {{ __('common.import') }}
            </a>
        </div>
    </div>

    <div class="row g-4">
        {{-- Left Column: Entity + Filters + Format --}}
        <div class="col-lg-5">
            <div class="d-flex flex-column gap-4">

                {{-- Entity Type --}}
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <label class="form-label fw-semibold mb-3">
                            {{ __('export.data_type') }} <span class="text-danger">*</span>
                        </label>
                        @php
                            $entityIcons = [
                                'companies'        => ['color' => '#4361ee', 'path' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
                                'departments'      => ['color' => '#0ea5e9', 'path' => 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z'],
                                'services'         => ['color' => '#f59e0b', 'path' => 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
                                'employees'        => ['color' => '#10b981', 'path' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'],
                                'leave_types'      => ['color' => '#ef4444', 'path' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
                                'leaves'           => ['color' => '#8b5cf6', 'path' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
                                'absences'         => ['color' => '#ec4899', 'path' => 'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636'],
                                'overtimes'        => ['color' => '#f97316', 'path' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
                                'payslips'         => ['color' => '#06b6d4', 'path' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                                'tickings'         => ['color' => '#14b8a6', 'path' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4'],
                                'advance_salaries' => ['color' => '#a855f7', 'path' => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z'],
                                'audit_logs'       => ['color' => '#6b7280', 'path' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01'],
                            ];
                        @endphp
                        <div class="row g-2">
                            @foreach($availableEntities as $entity)
                            @php $icon = $entityIcons[$entity['slug']] ?? ['color' => '#6b7280', 'path' => 'M4 6h16M4 10h16M4 14h16M4 18h16']; @endphp
                            <div class="col-6 col-md-4">
                                <div class="entity-card {{ $entitySlug === $entity['slug'] ? 'selected' : '' }}"
                                     wire:click="$set('entitySlug', '{{ $entity['slug'] }}')">
                                    <svg width="24" height="24" fill="none" stroke="{{ $icon['color'] }}" viewBox="0 0 24 24" class="mb-1">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $icon['path'] }}"/>
                                    </svg>
                                    <div class="fw-semibold" style="font-size:.75rem">{{ $entity['name'] }}</div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Filters --}}
                @if($entitySlug)
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h6 class="fw-semibold mb-3">
                            <svg class="icon icon-xs me-2 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                            </svg>
                            {{ __('export.search_filter') }}
                        </h6>

                        <div class="mb-3">
                            <input wire:model.live.debounce.500ms="searchQuery" type="text" class="form-control form-control-sm"
                                   placeholder="{{ __('export.search_placeholder') }}">
                        </div>

                        @if(in_array($entitySlug, ['employees', 'departments', 'services', 'leaves', 'absences', 'overtimes', 'payslips', 'tickings', 'advance_salaries']))
                        <div class="mb-3">
                            <label class="form-label small text-muted">{{ __('export.filter_company') }}</label>
                            <select wire:model.live="selectedCompanyId" class="form-select form-select-sm">
                                <option value="">{{ __('export.all_companies') }}</option>
                                @foreach($companies as $company)
                                <option value="{{ $company['id'] }}">{{ $company['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif

                        @if($selectedCompanyId && in_array($entitySlug, ['employees', 'services', 'leaves', 'overtimes', 'payslips', 'tickings']))
                        <div class="mb-3">
                            <label class="form-label small text-muted">{{ __('export.filter_department') }}</label>
                            <select wire:model.live="selectedDepartmentId" class="form-select form-select-sm">
                                <option value="">{{ __('export.all_departments') }}</option>
                                @foreach($departments as $dept)
                                <option value="{{ $dept['id'] }}">{{ $dept['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif

                        @if($selectedDepartmentId && in_array($entitySlug, ['employees', 'tickings']))
                        <div class="mb-0">
                            <label class="form-label small text-muted">{{ __('services.service') }}</label>
                            <select wire:model.live="selectedServiceId" class="form-select form-select-sm">
                                <option value="">{{ __('import.all_services') }}</option>
                                @foreach($services as $svc)
                                <option value="{{ $svc['id'] }}">{{ $svc['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                    </div>
                </div>

                {{-- Format + Download --}}
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <label class="form-label fw-semibold mb-3">{{ __('export.format') }}</label>
                        <div class="d-flex gap-2 mb-4">
                            <label class="format-card d-flex align-items-center gap-2 mb-0 {{ $exportFormat === 'xlsx' ? 'selected' : '' }}">
                                <input wire:model.live="exportFormat" type="radio" name="exportFormat" value="xlsx" class="d-none">
                                <svg width="20" height="20" fill="none" stroke="#10b981" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <div>
                                    <div class="fw-semibold small">Excel</div>
                                    <div class="text-muted" style="font-size:.72rem">.xlsx</div>
                                </div>
                            </label>
                            <label class="format-card d-flex align-items-center gap-2 mb-0 {{ $exportFormat === 'csv' ? 'selected' : '' }}">
                                <input wire:model.live="exportFormat" type="radio" name="exportFormat" value="csv" class="d-none">
                                <svg width="20" height="20" fill="none" stroke="#0ea5e9" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                </svg>
                                <div>
                                    <div class="fw-semibold small">CSV</div>
                                    <div class="text-muted" style="font-size:.72rem">.csv</div>
                                </div>
                            </label>
                        </div>

                        <button wire:click="export" class="btn btn-dark w-100 rounded-pill" wire:loading.attr="disabled"
                            @if(empty($selectedColumns)) disabled @endif>
                            <span wire:loading.remove wire:target="export">
                                <svg class="icon icon-xs me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                {{ __('export.download') }}
                            </span>
                            <span wire:loading wire:target="export">
                                <span class="spinner-border spinner-border-sm me-2"></span>
                                {{ __('export.generating') }}
                            </span>
                        </button>
                    </div>
                </div>
                @endif
            </div>
        </div>

        {{-- Right Column: Column Selection --}}
        <div class="col-lg-7">
            @if($entitySlug && !empty($columnDefinitions))
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h6 class="fw-semibold mb-1">{{ __('export.select_columns') }}</h6>
                            <p class="text-muted mb-0" style="font-size:.78rem">{{ __('export.columns_desc', ['count' => count($selectedColumns)]) }}</p>
                        </div>
                        <div class="d-flex gap-2">
                            <button wire:click="selectAllColumns" class="btn btn-sm btn-outline-dark rounded-pill px-3">{{ __('export.select_all') }}</button>
                            <button wire:click="deselectAllColumns" class="btn btn-sm btn-outline-secondary rounded-pill px-3">{{ __('export.deselect_all') }}</button>
                        </div>
                    </div>

                    <div class="row g-2">
                        @foreach($columnDefinitions as $colDef)
                        @php $isSelected = in_array($colDef['field'], $selectedColumns); @endphp
                        <div class="col-md-6">
                            <div class="col-chip {{ $isSelected ? 'selected' : '' }}"
                                 wire:click="toggleColumn('{{ $colDef['field'] }}')">
                                <div class="chip-check">
                                    @if($isSelected)
                                    <svg width="12" height="12" fill="none" stroke="#fff" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    @endif
                                </div>
                                <span class="small fw-medium {{ $isSelected ? 'text-dark' : 'text-muted' }}">{{ $colDef['label'] }}</span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @else
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex flex-column align-items-center justify-content-center text-center py-5">
                    <svg width="48" height="48" fill="none" stroke="#cbd5e0" viewBox="0 0 24 24" class="mb-3">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <h6 class="text-muted fw-semibold mb-1">{{ __('export.select_type_to_see_columns') }}</h6>
                    <p class="text-muted small mb-0">{{ __('export.columns_instruction') }}</p>
                </div>
            </div>
            @endif
        </div>
    </div>

    </div> {{-- /.export-wizard --}}
</div>
