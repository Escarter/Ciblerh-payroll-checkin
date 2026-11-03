<div>
    <x-alert />
    <div class="container pt-3 pt-lg-4 pb-4 pb-lg-3 text-white">
        <div class="d-flex flex-wrap align-items-center justify-content-between">
            <a href="{{ route('employee.dashboard') }}" wire:navigate>
                <svg class="icon me-1 text-gray-500 bg-gray-300 rounded-circle p-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            </a>
            <div><x-layouts.navigation.employee-nav /></div>
        </div>
        <div class='d-flex flex-wrap justify-content-between align-items-center pt-3'>
            <div>
                <h1 class='fw-bold display-4 text-gray-600 d-inline-flex align-items-end'>
                    <svg class="icon icon-md me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    <span>{{ __('annual_declarations.annual_declarations') }}</span>
                </h1>
                <p class="text-gray-800">{{ __('annual_declarations.employee_intro') }}</p>
            </div>
        </div>

        <div class="row py-2 text-gray-600 mb-2">
            <div class="col-md-4">
                <label for="orderBy">{{__('common.order_by')}}: <small class="text-muted">({{ __('common.optional') }})</small></label>
                <select wire:model.live="orderBy" id="orderBy" class="form-select">
                    <option value="year">{{__('employees.year')}}</option>
                    <option value="created_at">{{__('common.created_date')}}</option>
                </select>
            </div>
            <div class="col-md-4">
                <label for="direction">{{__('common.order_direction')}}: <small class="text-muted">({{ __('common.optional') }})</small></label>
                <select wire:model.live="orderAsc" id="direction" class="form-select">
                    <option value="asc">{{__('common.ascending')}}</option>
                    <option value="desc">{{__('common.descending')}}</option>
                </select>
            </div>
            <div class="col-md-4">
                <label for="perPage">{{__('common.items_per_page')}}: <small class="text-muted">({{ __('common.optional') }})</small></label>
                <select wire:model.live="perPage" id="perPage" class="form-select">
                    @foreach([5, 10, 15, 20, 25] as $n)
                        <option value="{{ $n }}">{{ $n }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        @if(count($declarations) > 0)
        <div class="card">
            <div class="table-responsive pb-4 text-gray-700">
                <table class="table table-bordered table-hover align-items-center dataTable">
                    <thead>
                        <tr>
                            <th class="border-0 px-4 py-2 text-muted fw-medium">{{__('employees.year')}}</th>
                            <th class="border-0 px-4 py-2 text-muted fw-medium">{{ __('annual_declarations.annual_declaration') }}</th>
                            <th class="border-0 px-4 py-2 text-muted fw-medium">{{__('employees.sent_date')}}</th>
                            <th class="border-0 px-4 py-2 text-muted fw-medium d-flex justify-content-center">{{__('employees.download')}}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($declarations as $declaration)
                        <tr wire:key="decl-{{ $declaration->id }}">
                            <td><span class="fw-bold">{{ $declaration->year }}</span></td>
                            <td><span class="fs-normal">{{ __('annual_declarations.annual_declaration') }}</span></td>
                            <td><span class="fw-normal">{{ $declaration->email_sent_at ?? $declaration->created_at }}</span></td>
                            <td>
                                <div class='d-flex justify-content-center gap-2'>
                                    <a href="{{ route('employee.annual-declaration.view-pdf', $declaration->id) }}" class="text-info" target="_blank" title="{{__('payslips.view_pdf')}}">
                                        <svg class="icon icon-xs" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </a>
                                    <a href='#' class="text-primary" wire:click.prevent="download({{ $declaration->id }})" wire:loading.remove wire:target="download({{ $declaration->id }})" title="{{__('employees.download')}}">
                                        <svg class="icon icon-xs" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 7.5h-.75A2.25 2.25 0 004.5 9.75v7.5a2.25 2.25 0 002.25 2.25h7.5a2.25 2.25 0 002.25-2.25v-7.5a2.25 2.25 0 00-2.25-2.25h-.75m-6 3.75l3 3m0 0l3-3m-3 3V1.5m6 9h.75a2.25 2.25 0 012.25 2.25v7.5a2.25 2.25 0 01-2.25 2.25h-7.5a2.25 2.25 0 01-2.25-2.25v-.75" />
                                        </svg>
                                    </a>
                                    <div class="text-center" wire:loading wire:target="download({{ $declaration->id }})">
                                        <div class="spinner-grow text-grey-300" style="width: 0.5rem; height: 0.5rem;" role="status"></div>
                                        <div class="spinner-grow text-grey-300" style="width: 0.5rem; height: 0.5rem;" role="status"></div>
                                        <div class="spinner-grow text-grey-300" style="width: 0.5rem; height: 0.5rem;" role="status"></div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="py-3 px-3">{{ $declarations->links() }}</div>
            </div>
        </div>
        @else
        <div class='border-prim rounded p-4 d-flex justify-content-center align-items-center flex-column'>
            <img src="{{asset('/img/empty.svg')}}" alt='{{__("common.empty")}}' class="text-center w-25 h-25">
            <div class="text-center text-gray-800 mt-2">
                <h4 class="fs-4 fw-bold">{{__('common.oops_nothing_here')}}</h4>
                <p>{{ __('annual_declarations.no_declarations_yet') }}</p>
            </div>
        </div>
        @endif
    </div>
</div>
