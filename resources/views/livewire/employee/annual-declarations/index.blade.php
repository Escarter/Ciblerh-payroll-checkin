<div>
    <x-alert />
    <div class="container pt-3 pt-lg-4 pb-4 pb-lg-3 text-white">
        <div class="d-flex flex-wrap align-items-center justify-content-between">
            <a href="{{ route('employee.dashboard') }}" wire:navigate>
                <svg class="icon me-1 text-gray-500 bg-gray-300 rounded-circle p-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            </a>
            <div><x-layouts.navigation.employee-nav /></div>
        </div>
        <div class="pt-3">
            <h1 class="fw-bold display-4 text-gray-600">{{ __('annual_declarations.annual_declarations') }}</h1>
            <p class="text-gray-800">{{ __('annual_declarations.employee_intro') }}</p>
        </div>

        @if(count($declarations) > 0)
            <div class="row g-3">
                @foreach($declarations as $declaration)
                    <div class="col-md-6 col-xl-4" wire:key="decl-{{ $declaration->id }}">
                        <div class="card border-0 shadow h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="icon-shape icon-shape-primary rounded me-3">
                                        <svg class="icon icon-md" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                    </div>
                                    <div>
                                        <div class="h4 fw-extrabold mb-0 text-gray-800">{{ $declaration->year }}</div>
                                        <div class="small text-muted">{{ __('annual_declarations.annual_declaration') }}</div>
                                    </div>
                                </div>
                                <div class="small text-muted mb-3">
                                    {{ __('employees.sent_date') }}: <span class="text-gray-700">{{ optional($declaration->email_sent_at ?? $declaration->created_at)->format('Y-m-d') }}</span>
                                </div>
                                <div class="d-flex gap-2">
                                    <a href="{{ route('employee.annual-declaration.view-pdf', $declaration->id) }}" target="_blank" class="btn btn-sm btn-outline-gray-600 flex-fill">{{ __('payslips.view_pdf') }}</a>
                                    <button class="btn btn-sm btn-gray-800 flex-fill" wire:click="download({{ $declaration->id }})" wire:loading.attr="disabled" wire:target="download({{ $declaration->id }})">{{ __('employees.download') }}</button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="py-3">{{ $declarations->links() }}</div>
        @else
            <div class="card border-0 shadow">
                <div class="card-body text-center py-5">
                    <svg class="icon icon-lg text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    <div class="text-gray-700">{{ __('annual_declarations.no_declarations_yet') }}</div>
                </div>
            </div>
        @endif
    </div>
</div>
