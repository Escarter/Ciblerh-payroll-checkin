<div>
    <x-alert />

    <div class="py-2 pb-2">
        <div class="d-flex justify-content-between w-100 flex-wrap mb-4 align-items-center">
            <div>
                <nav aria-label="breadcrumb" class="d-none d-md-inline-block">
                    <ol class="breadcrumb breadcrumb-dark breadcrumb-transparent">
                        <li class="breadcrumb-item"><a href="{{ route('portal.dashboard') }}" wire:navigate>{{ __('dashboard.home') }}</a></li>
                        <li class="breadcrumb-item active" aria-current="page">{{ __('annual_declarations.annual_declarations') }}</li>
                    </ol>
                </nav>
                <h1 class="h4 d-flex align-items-center">
                    <span class="icon-shape icon-shape-primary rounded me-2" style="width:2.25rem;height:2.25rem;">
                        <svg class="icon icon-xs" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    </span>
                    {{ __('annual_declarations.annual_declarations') }}
                </h1>
                <p class="mb-0 text-muted">{{ __('annual_declarations.manage_processes') }}</p>
            </div>
        </div>
    </div>

    {{-- Summary cards --}}
    <div class="row mb-2">
        @php
            $cards = [
                ['label' => __('annual_declarations.stat_processes'), 'value' => $stats['processes'], 'tone' => 'tertiary', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
                ['label' => __('annual_declarations.stat_in_progress'), 'value' => $stats['processing'], 'tone' => 'info', 'icon' => 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15'],
                ['label' => __('annual_declarations.stat_delivered'), 'value' => $stats['sent'], 'tone' => 'success', 'icon' => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
                ['label' => __('annual_declarations.stat_to_review'), 'value' => $stats['review'], 'tone' => 'warning', 'icon' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'],
            ];
        @endphp
        @foreach ($cards as $card)
            <div class="col-6 col-xl-3 mb-3">
                <div class="card border-0 shadow h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="icon-shape icon-shape-{{ $card['tone'] }} rounded me-3">
                            <svg class="icon icon-md" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $card['icon'] }}"></path></svg>
                        </div>
                        <div>
                            <div class="small text-muted text-uppercase fw-bold">{{ $card['label'] }}</div>
                            <div class="h3 fw-extrabold mb-0">{{ number_format($card['value'], 0, ',', ' ') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row">
        @can('payslip-create')
        <div class="col-lg-4 mb-4">
            <div class="card border-0 shadow">
                <div class="card-header border-bottom bg-transparent py-3">
                    <h2 class="h6 mb-0">{{ __('annual_declarations.send_declarations') }}</h2>
                </div>
                <div class="card-body">
                    <form wire:submit="send">
                        @hasanyrole('manager|admin')
                        <div class="mb-3">
                            <label class="form-label small fw-bold" for="company">{{ __('companies.company') }} <small class="text-muted">({{ __('common.optional') }})</small></label>
                            <select wire:model.live="company_id" class="form-select @error('company_id') is-invalid @enderror" id="company">
                                <option value="">{{ __('payslips.select_company') }}</option>
                                @foreach ($companies as $company)
                                    <option value="{{ $company->id }}" wire:key="company-{{ $company->id }}">{{ $company->name }}</option>
                                @endforeach
                            </select>
                            @error('company_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        @endhasanyrole

                        <div class="row">
                            <div class="col-8 mb-3">
                                <label class="form-label small fw-bold" for="department">{{ __('departments.department') }} <small class="text-muted">({{ __('common.optional') }})</small></label>
                                <select wire:model="department_id" class="form-select @error('department_id') is-invalid @enderror" id="department">
                                    <option value="">{{ __('annual_declarations.all_departments') }}</option>
                                    @foreach ($departments as $department)
                                        <option value="{{ $department->id }}" wire:key="dept-{{ $department->id }}">{{ $department->name }}</option>
                                    @endforeach
                                </select>
                                @error('department_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-4 mb-3">
                                <label class="form-label small fw-bold" for="year">{{ __('common.year') }} <span class="text-danger">*</span></label>
                                <select wire:model="year" id="year" class="form-select @error('year') is-invalid @enderror">
                                    @for ($y = now()->year; $y >= now()->year - 6; $y--)
                                        <option value="{{ $y }}">{{ $y }}</option>
                                    @endfor
                                </select>
                                @error('year') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="mb-3"
                             x-data="{ uploading: false, progress: 0, dragging: false }"
                             x-on:livewire-upload-start="uploading = true; progress = 0"
                             x-on:livewire-upload-finish="uploading = false"
                             x-on:livewire-upload-error="uploading = false"
                             x-on:livewire-upload-progress="progress = $event.detail.progress">
                            <label class="form-label small fw-bold">{{ __('annual_declarations.declaration_pdf') }} <span class="text-danger">*</span></label>
                            <label for="declaration_file"
                                   class="rf-dropzone w-100"
                                   :class="dragging ? 'is-dragging' : ''"
                                   x-on:dragover.prevent="dragging = true" x-on:dragleave.prevent="dragging = false"
                                   x-on:drop.prevent="dragging = false; $refs.file.files = $event.dataTransfer.files; $refs.file.dispatchEvent(new Event('change', { bubbles: true }))">
                                @if ($declaration_file)
                                    <svg class="icon icon-md text-success mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    <div class="fw-bold text-break">{{ $declaration_file->getClientOriginalName() }}</div>
                                    <div class="small text-muted">{{ number_format($declaration_file->getSize() / 1048576, 2) }} MB · {{ __('annual_declarations.click_to_replace') }}</div>
                                @else
                                    <svg class="icon icon-md text-gray-500 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                                    <div class="fw-bold">{{ __('annual_declarations.drop_pdf_here') }}</div>
                                    <div class="small text-muted">{{ __('annual_declarations.pdf_hint') }}</div>
                                @endif
                                <input x-ref="file" id="declaration_file" type="file" wire:model="declaration_file" accept="application/pdf" class="d-none">
                            </label>
                            <div x-show="uploading" x-cloak class="progress mt-2" style="height:6px;">
                                <div class="progress-bar" role="progressbar" :style="`width:${progress}%`"></div>
                            </div>
                            @error('declaration_file') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>

                        <button type="submit" class="btn btn-gray-800 w-100 d-flex align-items-center justify-content-center"
                                wire:loading.attr="disabled" wire:target="send,declaration_file"
                                @disabled(empty($declaration_file))>
                            <span wire:loading.remove wire:target="send">{{ __('annual_declarations.start_processing') }}</span>
                            <span wire:loading wire:target="send"><span class="spinner-border spinner-border-sm me-2"></span>{{ __('annual_declarations.starting') }}</span>
                        </button>
                    </form>

                    <hr class="my-4">
                    <div class="small text-muted">
                        <div class="fw-bold text-gray-700 mb-2">{{ __('annual_declarations.how_it_works') }}</div>
                        <ol class="ps-3 mb-0">
                            <li class="mb-1">{{ __('annual_declarations.step_split') }}</li>
                            <li class="mb-1">{{ __('annual_declarations.step_match') }}</li>
                            <li class="mb-1">{{ __('annual_declarations.step_send') }}</li>
                            <li>{{ __('annual_declarations.step_review') }}</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        @endcan

        <div class="@can('payslip-create') col-lg-8 @else col-12 @endcan mb-4"
             @if($stats['processing'] > 0) wire:poll.5s @endif>
            <div class="card border-0 shadow">
                <div class="card-header border-bottom bg-transparent py-3">
                    <h2 class="h6 mb-0">{{ __('annual_declarations.history') }}</h2>
                </div>
                <div class="table-responsive">
                    <table class="table table-centered table-nowrap mb-0 rounded">
                        <thead class="thead-light">
                            <tr>
                                <th class="border-0">{{ __('common.year') }}</th>
                                <th class="border-0">{{ __('companies.company') }}</th>
                                <th class="border-0" style="min-width:150px;">{{ __('annual_declarations.delivery') }}</th>
                                <th class="border-0">{{ __('annual_declarations.to_review') }}</th>
                                <th class="border-0">{{ __('common.status') }}</th>
                                <th class="border-0"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($processes as $process)
                                @php
                                    $tone = ['processing' => 'info', 'successful' => 'success', 'failed' => 'danger'][$process->status] ?? 'secondary';
                                    $pct = $process->sendable_count > 0 ? round($process->sent_count / $process->sendable_count * 100) : 0;
                                @endphp
                                <tr wire:key="process-{{ $process->id }}">
                                    <td><span class="fw-extrabold fs-6">{{ $process->year }}</span></td>
                                    <td>
                                        <div class="fw-bold">{{ $process->company?->name }}</div>
                                        <div class="small text-muted">{{ $process->department?->name ?? __('annual_declarations.all_departments') }} · {{ $process->created_at->diffForHumans() }}</div>
                                    </td>
                                    <td>
                                        @if($process->status === 'processing')
                                            <div class="small text-muted mb-1">{{ (int) $process->percentage_completion }}%</div>
                                            <div class="progress" style="height:6px;"><div class="progress-bar bg-info progress-bar-striped progress-bar-animated" style="width:{{ max((int) $process->percentage_completion, 5) }}%"></div></div>
                                        @else
                                            <div class="small mb-1"><span class="fw-bold">{{ $process->sent_count }}</span> / {{ $process->sendable_count }}</div>
                                            <div class="progress" style="height:6px;"><div class="progress-bar bg-success" style="width:{{ $pct }}%"></div></div>
                                        @endif
                                    </td>
                                    <td>
                                        @if($process->review_count > 0)
                                            <span class="badge bg-warning text-dark">{{ $process->review_count }}</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td><span class="badge bg-{{ $tone }}">{{ __('annual_declarations.status_' . $process->status) }}</span></td>
                                    <td class="text-end">
                                        <a href="{{ route('portal.annual-declarations.details', $process->id) }}" wire:navigate class="btn btn-sm btn-outline-gray-600">
                                            {{ __('common.details') }}
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5">
                                        <svg class="icon icon-lg text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                        <div class="fw-bold text-gray-700">{{ __('annual_declarations.no_processes') }}</div>
                                        <div class="small text-muted">{{ __('annual_declarations.no_processes_hint') }}</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($processes->hasPages())
                    <div class="card-footer border-0">{{ $processes->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</div>
