<div>
    <x-alert />

    {{-- ══════════════════════════════════════════════════════════════════
         Scoped Styles
         ══════════════════════════════════════════════════════════════════ --}}
    <style>
        .import-wizard .step-connector {
            flex: 1;
            height: 3px;
            background: #e2e8f0;
            margin: 0 12px;
            border-radius: 2px;
            transition: background .3s ease;
        }
        .import-wizard .step-connector.completed { background: #1cc88a; }

        .import-wizard .step-circle {
            width: 42px; height: 42px; min-width: 42px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: .85rem;
            transition: all .3s ease;
            border: 2px solid transparent;
        }
        .import-wizard .step-circle.active   { background: #262b40; color: #fff; border-color: #262b40; box-shadow: 0 0 0 4px rgba(38,43,64,.15); }
        .import-wizard .step-circle.done     { background: #1cc88a; color: #fff; border-color: #1cc88a; }
        .import-wizard .step-circle.pending  { background: #f5f6fa; color: #93a5be; border-color: #e2e8f0; }

        .import-wizard .mode-card {
            border: 2px solid #e2e8f0;
            border-radius: .75rem;
            padding: 1rem 1.25rem;
            cursor: pointer;
            transition: all .2s ease;
            position: relative;
        }
        .import-wizard .mode-card:hover { border-color: #b5c0d0; background: #f8f9fc; }
        .import-wizard .mode-card.selected { border-color: #262b40; background: #f0f1f5; }
        .import-wizard .mode-card .mode-icon {
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }

        .import-wizard .upload-zone {
            border: 2px dashed #cbd5e0;
            border-radius: 1rem;
            padding: 2.5rem 1.5rem;
            text-align: center;
            cursor: pointer;
            transition: all .25s ease;
            position: relative;
            background: #fafbfc;
        }
        .import-wizard .upload-zone:hover { border-color: #93a5be; background: #f0f2f8; }
        .import-wizard .upload-zone.dragging { border-color: #262b40; background: #eef0f6; }
        .import-wizard .upload-zone.has-file { border-color: #1cc88a; background: #f0fdf4; border-style: solid; }
        .import-wizard .upload-zone input[type="file"] { position: absolute; inset: 0; opacity: 0; cursor: pointer; }

        .import-wizard .entity-card {
            border: 2px solid #e2e8f0;
            border-radius: .75rem;
            padding: .75rem;
            cursor: pointer;
            transition: all .2s ease;
            text-align: center;
        }
        .import-wizard .entity-card:hover { border-color: #b5c0d0; transform: translateY(-1px); }
        .import-wizard .entity-card.selected { border-color: #262b40; background: #f0f1f5; }
        .import-wizard .entity-card .entity-icon {
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto .5rem;
        }

        .import-wizard .option-switch { display: flex; align-items: flex-start; gap: .75rem; padding: .75rem 1rem; border-radius: .625rem; transition: background .2s; }
        .import-wizard .option-switch:hover { background: #f8f9fc; }

        .import-wizard .mapping-row { transition: background .15s; }
        .import-wizard .mapping-row:hover { background: #f8f9fc; }
        .import-wizard .mapping-arrow { color: #93a5be; display: flex; align-items: center; justify-content: center; }

        .import-wizard .template-banner {
            background: linear-gradient(135deg, #f0f1f5 0%, #e8eaf3 100%);
            border-radius: .75rem;
            padding: 1.25rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .import-wizard .stat-card-modern {
            border-radius: .75rem;
            padding: 1.25rem;
            text-align: center;
            border: 1px solid transparent;
        }
    </style>

    <div class="import-wizard">

    {{-- ══════════════════════════════════════════════════════════════════
         Page Header
         ══════════════════════════════════════════════════════════════════ --}}
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
        <div>
            <h1 class="h4 fw-bold mb-1">{{ __('import.wizard_title') }}</h1>
            <p class="text-muted mb-0 small">{{ __('import.wizard_subtitle') }}</p>
        </div>
        <div class="d-flex gap-2">
            @if($currentStep > 1)
            <button wire:click="resetWizard" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                {{ __('import.start_over') }}
            </button>
            @endif
            <a href="{{ route('portal.import-jobs.index') }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
                {{ __('import.view_import_history') }}
            </a>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════
         Progress Steps
         ══════════════════════════════════════════════════════════════════ --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3 px-4">
            @php
                $steps = [
                    1 => __('import.step_upload'),
                    2 => __('import.step_map'),
                    3 => __('import.step_preview'),
                    4 => __('import.step_import'),
                ];
            @endphp
            <div class="d-flex align-items-center justify-content-between">
                @foreach($steps as $num => $label)
                    {{-- Step circle + label --}}
                    <div class="d-flex align-items-center gap-2 {{ $num < $currentStep ? 'cursor-pointer' : '' }}"
                         @if($num < $currentStep) wire:click="goToStep({{ $num }})" @endif>
                        <div class="step-circle {{ $currentStep === $num ? 'active' : ($currentStep > $num ? 'done' : 'pending') }}">
                            @if($currentStep > $num)
                                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                </svg>
                            @else
                                {{ $num }}
                            @endif
                        </div>
                        <span class="fw-semibold small d-none d-lg-inline {{ $currentStep === $num ? 'text-dark' : ($currentStep > $num ? 'text-success' : 'text-muted') }}">
                            {{ $label }}
                        </span>
                    </div>
                    {{-- Connector --}}
                    @if(!$loop->last)
                        <div class="step-connector {{ $currentStep > $num ? 'completed' : '' }}"></div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════
         STEP 1 — Upload & Configure
         ══════════════════════════════════════════════════════════════════ --}}
    @if($currentStep === 1)
    <div class="row g-4">
        {{-- ────── Left Column: Entity + Mode ────── --}}
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">

                    {{-- Entity Type Selection (cards) --}}
                    <label class="form-label fw-semibold mb-3">{{ __('import.entity_type') }} <span class="text-danger">*</span></label>
                    @php
                        $entityIcons = [
                            'companies'   => ['color' => '#4361ee', 'path' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
                            'departments' => ['color' => '#0ea5e9', 'path' => 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z'],
                            'services'    => ['color' => '#f59e0b', 'path' => 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
                            'employees'   => ['color' => '#10b981', 'path' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'],
                            'leave_types' => ['color' => '#ef4444', 'path' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
                        ];
                    @endphp
                    <div class="row g-2 mb-4">
                        @foreach($availableEntities as $entity)
                        @php $icon = $entityIcons[$entity['slug']] ?? ['color' => '#6b7280', 'path' => 'M4 6h16M4 10h16M4 14h16M4 18h16']; @endphp
                        <div class="col-6 col-md-4">
                            <div class="entity-card {{ $entitySlug === $entity['slug'] ? 'selected' : '' }}"
                                 wire:click="$set('entitySlug', '{{ $entity['slug'] }}')">
                                <div class="entity-icon">
                                    <svg width="28" height="28" fill="none" stroke="{{ $icon['color'] }}" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $icon['path'] }}"/>
                                    </svg>
                                </div>
                                <div class="fw-semibold small">{{ $entity['name'] }}</div>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    {{-- Import Mode --}}
                    <label class="form-label fw-semibold mb-3">{{ __('import.import_mode') }} <span class="text-danger">*</span></label>
                    <div class="d-flex flex-column gap-2 mb-2">
                        @php
                            $modes = [
                                'create_only' => ['icon' => 'M12 6v6m0 0v6m0-6h6m-6 0H6',                          'color' => '#10b981'],
                                'update_only' => ['icon' => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z', 'color' => '#0ea5e9'],
                                'upsert'      => ['icon' => 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15', 'color' => '#f59e0b'],
                            ];
                        @endphp
                        @foreach($modes as $modeVal => $modeInfo)
                        <label class="mode-card d-flex align-items-center gap-3 mb-0 {{ $importMode === $modeVal ? 'selected' : '' }}">
                            <input wire:model.live="importMode" type="radio" name="importMode" value="{{ $modeVal }}" class="form-check-input mt-0 d-none">
                            <div class="mode-icon">
                                <svg width="22" height="22" fill="none" stroke="{{ $modeInfo['color'] }}" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $modeInfo['icon'] }}"/>
                                </svg>
                            </div>
                            <div class="flex-fill">
                                <div class="fw-semibold small text-dark">{{ __('import.mode_' . $modeVal) }}</div>
                                <div class="text-muted" style="font-size:.8rem">{{ __('import.mode_' . $modeVal . '_desc') }}</div>
                            </div>
                            @if($importMode === $modeVal)
                            <svg class="icon icon-xs text-success flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            @endif
                        </label>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- ────── Right Column: Context + Options + File ────── --}}
        <div class="col-lg-5">
            <div class="d-flex flex-column gap-4">

                {{-- Context Selection --}}
                @if($entitySlug && in_array($entitySlug, ['employees', 'departments', 'services']))
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h6 class="fw-semibold mb-3">
                            <svg class="icon icon-xs me-2 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                            </svg>
                            {{ __('import.context_company') }}
                        </h6>
                        <select wire:model.live="selectedCompanyId" class="form-select form-select-sm mb-2">
                            <option value="">{{ __('import.all_companies') }}</option>
                            @foreach($companies as $company)
                            <option value="{{ $company['id'] }}">{{ $company['name'] }} {{ isset($company['code']) ? '('.$company['code'].')' : '' }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted" style="font-size:.78rem">{{ __('import.context_company_desc') }}</small>

                        @if($selectedCompanyId && in_array($entitySlug, ['employees', 'services']))
                        <select wire:model.live="selectedDepartmentId" class="form-select form-select-sm mt-3">
                            <option value="">{{ __('import.all_departments') }}</option>
                            @foreach($departments as $dept)
                            <option value="{{ $dept['id'] }}">{{ $dept['name'] }}</option>
                            @endforeach
                        </select>
                        @endif

                        @if($selectedDepartmentId && $entitySlug === 'employees')
                        <select wire:model.live="selectedServiceId" class="form-select form-select-sm mt-3">
                            <option value="">{{ __('import.all_services') }}</option>
                            @foreach($services as $svc)
                            <option value="{{ $svc['id'] }}">{{ $svc['name'] }}</option>
                            @endforeach
                        </select>
                        @endif
                    </div>
                </div>
                @endif

                {{-- Options --}}
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h6 class="fw-semibold mb-3">
                            <svg class="icon icon-xs me-2 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            {{ __('import.options') }}
                        </h6>
                        <div class="option-switch">
                            <div class="form-check form-switch mb-0">
                                <input wire:model="autoCreateEntities" class="form-check-input" type="checkbox" id="autoCreate" role="switch">
                            </div>
                            <div>
                                <label class="form-check-label fw-medium small text-dark" for="autoCreate">{{ __('import.auto_create_entities') }}</label>
                                <div class="text-muted" style="font-size:.78rem">{{ __('import.auto_create_desc') }}</div>
                            </div>
                        </div>

                        @if($entitySlug === 'employees')
                        <div class="option-switch mt-2">
                            <div class="form-check form-switch mb-0">
                                <input wire:model="sendWelcomeEmails" class="form-check-input" type="checkbox" id="welcomeEmails" role="switch">
                            </div>
                            <div>
                                <label class="form-check-label fw-medium small text-dark" for="welcomeEmails">{{ __('import.send_welcome_emails') }}</label>
                                <div class="text-muted" style="font-size:.78rem">{{ __('import.welcome_emails_desc') }}</div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                {{-- Template Download --}}
                @if($entitySlug)
                <div class="template-banner">
                    <svg width="32" height="32" fill="none" stroke="#4361ee" viewBox="0 0 24 24" class="flex-shrink-0">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <div class="flex-fill">
                        <div class="fw-semibold small">{{ __('import.need_template') }}</div>
                        <div class="text-muted" style="font-size:.78rem">{{ __('import.accepted_formats') }}: XLSX, XLS, CSV, TXT</div>
                    </div>
                    <button wire:click="downloadTemplate" class="btn btn-sm btn-dark rounded-pill px-3">
                        {{ __('import.download_template') }}
                    </button>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- File Upload Zone (full width) --}}
    <div class="card border-0 shadow-sm mt-4">
        <div class="card-body p-4">
            <label class="form-label fw-semibold">{{ __('import.select_file') }} <span class="text-danger">*</span></label>
            <div class="upload-zone {{ $file ? 'has-file' : '' }}"
                 x-data="{ isDragging: false }"
                 x-on:dragover.prevent="isDragging = true"
                 x-on:dragleave="isDragging = false"
                 x-on:drop.prevent="isDragging = false"
                 :class="{ 'dragging': isDragging }">

                @if($file)
                    <div class="d-flex align-items-center justify-content-center gap-3">
                        <svg width="44" height="44" fill="none" stroke="#10b981" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div class="text-start">
                            <div class="fw-semibold text-success">{{ $file->getClientOriginalName() }}</div>
                            <div class="text-muted small">{{ number_format($file->getSize() / 1024, 1) }} KB — {{ __('import.accepted_formats') }}</div>
                        </div>
                    </div>
                @else
                    <div class="mb-2">
                        <svg width="48" height="48" fill="none" stroke="#93a5be" viewBox="0 0 24 24" class="mb-2">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                        </svg>
                    </div>
                    <div class="fw-semibold small text-dark mb-1">{{ __('import.drag_drop_file') }}</div>
                    <div class="text-muted" style="font-size:.78rem">{{ __('import.accepted_formats') }}: XLSX, XLS, CSV, TXT ({{ __('common.max') }} 50MB)</div>
                @endif

                <input wire:model="file" type="file" id="importFile" accept=".xlsx,.xls,.csv,.txt">
            </div>
            @error('file') <div class="text-danger small mt-2">{{ $message }}</div> @enderror

            {{-- Loading indicator during upload --}}
            <div wire:loading wire:target="file" class="text-center mt-3">
                <span class="spinner-border spinner-border-sm text-primary me-2"></span>
                <span class="text-muted small">Uploading file...</span>
            </div>
        </div>
    </div>

    {{-- Step Actions --}}
    <div class="d-flex justify-content-between align-items-center mt-4">
        <small class="text-muted">{{ __('common.step') }} 1 {{ __('common.of') }} 4</small>
        <button wire:click="proceedToMapping" class="btn btn-dark rounded-pill px-4" wire:loading.attr="disabled"
            @if(!$file || !$entitySlug) disabled @endif>
            <span wire:loading.remove wire:target="proceedToMapping">
                {{ __('import.next_map_fields') }}
                <svg class="icon icon-xs ms-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                </svg>
            </span>
            <span wire:loading wire:target="proceedToMapping">
                <span class="spinner-border spinner-border-sm me-2"></span>
                {{ __('import.parsing_file') }}
            </span>
        </button>
    </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════════════
         STEP 2 — Field Mapping
         ══════════════════════════════════════════════════════════════════ --}}
    @if($currentStep === 2)
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4 pb-3">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
                <div>
                    <h5 class="fw-bold mb-1">{{ __('import.map_fields_title') }}</h5>
                    <p class="text-muted small mb-0">{{ __('import.map_fields_desc') }}</p>
                </div>
                <span class="badge bg-dark bg-opacity-10 text-dark rounded-pill px-3 py-2">
                    {{ count($csvHeaders) }} {{ __('import.columns_detected') }}
                </span>
            </div>

            @if(!empty($unmappedRequired))
            <div class="alert alert-warning d-flex align-items-start gap-2 mb-4" role="alert">
                <svg class="icon icon-xs mt-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div>
                    <strong class="small">{{ __('import.unmapped_required') }}:</strong>
                    <span class="small">{{ implode(', ', array_map(fn($f) => is_array($f) ? ($f['label'] ?? $f['field'] ?? '') : $f, $unmappedRequired)) }}</span>
                </div>
            </div>
            @endif

            {{-- Mapping Rows --}}
            <div class="border rounded-3 overflow-hidden mb-4">
                {{-- Header --}}
                <div class="row g-0 bg-light py-2 px-3 small fw-semibold text-muted border-bottom">
                    <div class="col-4">{{ __('import.csv_column') }}</div>
                    <div class="col-1 text-center">{{ __('import.confidence') }}</div>
                    <div class="col-1 text-center"></div>
                    <div class="col-4">{{ __('import.maps_to') }}</div>
                    <div class="col-2">{{ __('import.sample') }}</div>
                </div>

                {{-- Rows --}}
                @foreach($csvHeaders as $idx => $header)
                <div class="row g-0 mapping-row align-items-center py-2 px-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                    <div class="col-4">
                        <div class="fw-semibold small text-dark">{{ $header }}</div>
                        @if(!empty($rawRows) && isset($rawRows[0][$idx]))
                        <div class="text-muted" style="font-size:.75rem">{{ Str::limit($rawRows[0][$idx] ?? '', 45) }}</div>
                        @endif
                    </div>
                    <div class="col-1 text-center">
                        @php
                            $suggestion = $autoMappingSuggestions[$idx] ?? null;
                            $confidence = $suggestion['confidence'] ?? 0;
                        @endphp
                        @if($confidence >= 80)
                            <span class="badge rounded-pill bg-success" style="font-size:.7rem">{{ $confidence }}%</span>
                        @elseif($confidence >= 50)
                            <span class="badge rounded-pill bg-warning text-dark" style="font-size:.7rem">{{ $confidence }}%</span>
                        @elseif($confidence > 0)
                            <span class="badge rounded-pill bg-danger" style="font-size:.7rem">{{ $confidence }}%</span>
                        @else
                            <span class="badge rounded-pill bg-light text-muted" style="font-size:.7rem">—</span>
                        @endif
                    </div>
                    <div class="col-1 mapping-arrow">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </div>
                    <div class="col-4">
                        <select wire:model.live="fieldMapping.{{ $idx }}" class="form-select form-select-sm">
                            <option value="">— {{ __('import.skip_column') }} —</option>
                            @foreach($availableFields as $field)
                            <option value="{{ $field['field'] }}">
                                {{ $field['label'] }}@if($field['required']) *@endif
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-2 text-muted" style="font-size:.75rem">
                        @if(!empty($rawRows) && count($rawRows) > 1 && isset($rawRows[1][$idx]))
                            {{ Str::limit($rawRows[1][$idx] ?? '', 22) }}
                        @endif
                    </div>
                </div>
                @endforeach
            </div>

            {{-- Field Legend --}}
            <div class="row g-3">
                <div class="col-md-6">
                    <h6 class="text-muted mb-2" style="font-size:.78rem; text-transform:uppercase; letter-spacing:.04em">{{ __('import.required_fields') }}</h6>
                    <div class="d-flex flex-wrap gap-1">
                        @foreach($availableFields as $field)
                            @if($field['required'])
                            @php $mapped = in_array($field['field'], array_filter($fieldMapping)); @endphp
                            <span class="badge rounded-pill {{ $mapped ? 'bg-success' : 'bg-danger bg-opacity-75' }}" style="font-size:.72rem">
                                {{ $field['label'] }} {{ $mapped ? '✓' : '✕' }}
                            </span>
                            @endif
                        @endforeach
                    </div>
                </div>
                <div class="col-md-6">
                    <h6 class="text-muted mb-2" style="font-size:.78rem; text-transform:uppercase; letter-spacing:.04em">{{ __('import.optional_fields') }}</h6>
                    <div class="d-flex flex-wrap gap-1">
                        @foreach($availableFields as $field)
                            @if(!$field['required'])
                            @php $mapped = in_array($field['field'], array_filter($fieldMapping)); @endphp
                            <span class="badge rounded-pill {{ $mapped ? 'bg-info' : 'bg-light text-muted' }}" style="font-size:.72rem">
                                {{ $field['label'] }}
                            </span>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Step Actions --}}
    <div class="d-flex justify-content-between align-items-center mt-4">
        <small class="text-muted">{{ __('common.step') }} 2 {{ __('common.of') }} 4</small>
        <div class="d-flex gap-2">
            <button wire:click="goToStep(1)" class="btn btn-outline-secondary rounded-pill px-3">{{ __('common.back') }}</button>
            <button wire:click="proceedToPreview" class="btn btn-dark rounded-pill px-4" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="proceedToPreview">
                    {{ __('import.next_preview') }}
                    <svg class="icon icon-xs ms-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                </span>
                <span wire:loading wire:target="proceedToPreview">
                    <span class="spinner-border spinner-border-sm me-2"></span>
                    {{ __('import.validating') }}
                </span>
            </button>
        </div>
    </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════════════
         STEP 3 — Preview
         ══════════════════════════════════════════════════════════════════ --}}
    @if($currentStep === 3)
    {{-- Stat Cards --}}
    <div class="row g-3 mb-4">
        @php
            $previewStats = [
                ['value' => $totalRows,           'label' => __('import.total_rows'),     'color' => 'primary'],
                ['value' => count($previewData),   'label' => __('import.rows_previewed'), 'color' => 'info'],
                ['value' => $previewValidCount,    'label' => __('common.valid'),          'color' => 'success'],
                ['value' => $previewErrorCount,    'label' => __('common.errors'),         'color' => 'danger'],
            ];
        @endphp
        @foreach($previewStats as $stat)
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="stat-card-modern">
                    <div class="fw-extrabold h3 mb-1 text-{{ $stat['color'] }}">{{ $stat['value'] }}</div>
                    <div class="text-muted small">{{ $stat['label'] }}</div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <h5 class="fw-bold mb-3">{{ __('import.preview_title') }}</h5>

            @if($previewErrorCount === 0)
            <div class="alert alert-success d-flex align-items-center gap-2 mb-4" role="alert">
                <svg class="icon icon-xs flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="small">{{ __('import.all_rows_valid') }}</span>
            </div>
            @else
            <div class="alert alert-warning d-flex align-items-center gap-2 mb-4" role="alert">
                <svg class="icon icon-xs flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <span class="small">{{ __('import.preview_has_errors', ['valid' => $previewValidCount, 'errors' => $previewErrorCount]) }}</span>
            </div>
            @endif

            {{-- Preview Table --}}
            @if(count($previewData) > 0)
            <div class="table-responsive border rounded-3" style="max-height: 480px; overflow-y: auto;">
                <table class="table table-hover table-sm mb-0 align-middle">
                    <thead class="table-light sticky-top" style="z-index:2">
                        <tr>
                            <th class="text-center" style="width: 44px;">#</th>
                            <th class="text-center" style="width: 64px;">{{ __('common.status') }}</th>
                            @php
                                $mappedFieldNames = array_filter($fieldMapping);
                                $fieldLabels = collect($availableFields)->keyBy('field');
                            @endphp
                            @foreach($mappedFieldNames as $idx => $fieldName)
                                <th class="small">{{ $fieldLabels[$fieldName]['label'] ?? $fieldName }}</th>
                            @endforeach
                            <th class="small" style="min-width: 180px;">{{ __('common.errors') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($previewData as $row)
                        @php
                            $isValid = $row['valid'] ?? false;
                            $rowErrors = $row['errors'] ?? [];
                        @endphp
                        <tr class="{{ $isValid ? '' : 'table-danger' }}">
                            <td class="text-center text-muted small">{{ $row['row_number'] }}</td>
                            <td class="text-center">
                                @if($isValid)
                                <span class="badge rounded-pill bg-success" style="font-size:.68rem">{{ __('common.valid') }}</span>
                                @else
                                <span class="badge rounded-pill bg-danger" style="font-size:.68rem">{{ __('common.error') }}</span>
                                @endif
                            </td>
                            @foreach($mappedFieldNames as $idx => $fieldName)
                            <td style="font-size:.8rem">
                                {{ Str::limit($row['data'][$fieldName] ?? '', 30) }}
                            </td>
                            @endforeach
                            <td>
                                @if(!empty($rowErrors))
                                <div class="text-danger" style="font-size:.75rem">
                                    @foreach($rowErrors as $err)
                                    <div>• {{ is_array($err) ? ($err['message'] ?? json_encode($err)) : $err }}</div>
                                    @endforeach
                                </div>
                                @else
                                <span class="text-success" style="font-size:.8rem">✓</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif

            {{-- Summary Row --}}
            <div class="row mt-4 g-3">
                <div class="col-md-6">
                    <div class="border rounded-3 p-3">
                        <h6 class="fw-semibold small mb-2">{{ __('import.import_summary') }}</h6>
                        <ul class="list-unstyled mb-0" style="font-size:.82rem">
                            <li class="mb-1"><span class="text-muted">{{ __('import.entity_type') }}:</span> <strong>{{ collect($availableEntities)->firstWhere('slug', $entitySlug)['name'] ?? $entitySlug }}</strong></li>
                            <li class="mb-1"><span class="text-muted">{{ __('import.import_mode') }}:</span> <strong>{{ __('import.mode_' . $importMode) }}</strong></li>
                            <li><span class="text-muted">{{ __('import.total_rows') }}:</span> <strong>{{ $totalRows }}</strong></li>
                            @if($autoCreateEntities)
                            <li class="mt-1"><span class="text-muted">{{ __('import.auto_create_entities') }}:</span> <span class="badge bg-success bg-opacity-10 text-success rounded-pill" style="font-size:.7rem">{{ __('common.yes') }}</span></li>
                            @endif
                        </ul>
                    </div>
                </div>
                <div class="col-md-6">
                    @if($previewErrorCount > 0)
                    <div class="border border-warning rounded-3 p-3 bg-warning bg-opacity-10">
                        <h6 class="fw-semibold small text-warning mb-1">{{ __('import.warning') }}</h6>
                        <p class="mb-0" style="font-size:.82rem">{{ __('import.errors_will_be_skipped') }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Step Actions --}}
    <div class="d-flex justify-content-between align-items-center mt-4">
        <small class="text-muted">{{ __('common.step') }} 3 {{ __('common.of') }} 4</small>
        <div class="d-flex gap-2">
            <button wire:click="goToStep(2)" class="btn btn-outline-secondary rounded-pill px-3">{{ __('common.back') }}</button>
            <button wire:click="executeImport" class="btn btn-success rounded-pill px-4" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="executeImport">
                    <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    {{ __('import.start_import') }}
                </span>
                <span wire:loading wire:target="executeImport">
                    <span class="spinner-border spinner-border-sm me-2"></span>
                    {{ __('import.importing') }}
                </span>
            </button>
        </div>
    </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════════════
         STEP 4 — Results
         ══════════════════════════════════════════════════════════════════ --}}
    @if($currentStep === 4)
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4 p-lg-5">
            @if($isImporting)
            {{-- Processing --}}
            <div class="text-center py-5" wire:poll.5s="refreshImportStatus">
                <div class="mb-4">
                    <div class="spinner-border text-primary" style="width: 3.5rem; height: 3.5rem; border-width: .3rem;" role="status">
                        <span class="visually-hidden">{{ __('common.loading') }}...</span>
                    </div>
                </div>
                <h5 class="fw-bold mb-2">{{ __('import.processing') }}</h5>
                <p class="text-muted small mb-4">{{ __('import.processing_desc') }}</p>

                @if($totalRows > 0)
                <div class="mx-auto" style="max-width: 420px;">
                    <div class="d-flex justify-content-between small text-muted mb-1">
                        <span>{{ $processedRows }} {{ __('import.rows_processed') }}</span>
                        <span>{{ $totalRows > 0 ? round($processedRows / $totalRows * 100) : 0 }}%</span>
                    </div>
                    <div class="progress" style="height: 10px; border-radius: 5px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary"
                            role="progressbar"
                            style="width: {{ $totalRows > 0 ? ($processedRows / $totalRows * 100) : 0 }}%; border-radius: 5px;"></div>
                    </div>
                </div>
                @endif
            </div>
            @else
            {{-- Results --}}
            @php
                $hasErrors = ($importResult['stats']['errors'] ?? 0) > 0;
                $hasSuccess = (($importResult['stats']['created'] ?? 0) + ($importResult['stats']['updated'] ?? 0)) > 0;
            @endphp

            <div class="text-center mb-4 pt-2">
                @if($hasSuccess && !$hasErrors)
                    <div class="mb-3">
                        <svg width="64" height="64" fill="none" stroke="#10b981" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h4 class="fw-bold text-success mb-1">{{ __('import.import_complete') }}</h4>
                @elseif($hasSuccess && $hasErrors)
                    <div class="mb-3">
                        <svg width="64" height="64" fill="none" stroke="#f59e0b" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <h4 class="fw-bold text-warning mb-1">{{ __('import.import_partial') }}</h4>
                @else
                    <div class="mb-3">
                        <svg width="64" height="64" fill="none" stroke="#ef4444" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h4 class="fw-bold text-danger mb-1">{{ __('import.import_failed') }}</h4>
                @endif
                <p class="text-muted small mb-0">Import completed for {{ collect($availableEntities)->firstWhere('slug', $entitySlug)['name'] ?? $entitySlug }}</p>
            </div>

            {{-- Stat Cards --}}
            <div class="row g-3 mb-4">
                @php
                    $resultStats = [
                        ['value' => $importResult['stats']['total'] ?? 0,   'label' => __('import.total_processed'), 'color' => 'primary'],
                        ['value' => $importResult['stats']['created'] ?? 0, 'label' => __('import.created'),         'color' => 'success'],
                        ['value' => $importResult['stats']['updated'] ?? 0, 'label' => __('import.updated'),         'color' => 'info'],
                        ['value' => $importResult['stats']['errors'] ?? 0,  'label' => __('common.errors'),          'color' => 'danger'],
                    ];
                @endphp
                @foreach($resultStats as $rs)
                <div class="col-6 col-md-3">
                    <div class="stat-card-modern bg-white shadow-sm">
                        <div class="fw-extrabold h3 mb-1 text-{{ $rs['color'] }}">{{ $rs['value'] }}</div>
                        <div class="text-muted small">{{ $rs['label'] }}</div>
                    </div>
                </div>
                @endforeach
            </div>

            {{-- Error Details --}}
            @if(!empty($importResult['errors']))
            <div class="border border-danger rounded-3 overflow-hidden mb-4">
                <div class="bg-danger bg-opacity-10 px-4 py-2 d-flex align-items-center gap-2">
                    <svg class="icon icon-xs text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <h6 class="mb-0 text-danger small fw-semibold">{{ __('import.error_details') }}</h6>
                </div>
                <div class="table-responsive" style="max-height: 280px;">
                    <table class="table table-sm mb-0 align-middle">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th class="small" style="width: 70px;">{{ __('import.row') }}</th>
                                <th class="small" style="width: 140px;">{{ __('import.field') }}</th>
                                <th class="small">{{ __('import.error_message') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach(array_slice($importResult['errors'], 0, 50) as $error)
                            <tr>
                                <td style="font-size:.8rem">{{ $error['row'] ?? '—' }}</td>
                                <td style="font-size:.8rem">{{ $error['field'] ?? '—' }}</td>
                                <td style="font-size:.8rem">{{ is_array($error['message'] ?? null) ? json_encode($error['message']) : ($error['message'] ?? '—') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if(count($importResult['errors']) > 50)
                <div class="px-4 py-2 bg-light text-muted small border-top">
                    {{ __('import.showing_first_errors', ['shown' => 50, 'total' => count($importResult['errors'])]) }}
                </div>
                @endif
            </div>
            @endif

            {{-- Step Actions --}}
            <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                <small class="text-muted">{{ __('common.step') }} 4 {{ __('common.of') }} 4</small>
                <div class="d-flex gap-2">
                    <a href="{{ route('portal.import-jobs.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
                        {{ __('import.view_all_jobs') }}
                    </a>
                    <button wire:click="resetWizard" class="btn btn-dark rounded-pill px-4">
                        <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        {{ __('import.new_import') }}
                    </button>
                </div>
            </div>
            @endif
        </div>
    </div>
    @endif

    </div> {{-- /.import-wizard --}}
</div>
