<div>
    <x-alert />
    <div class="p-0">
        <div class="d-flex justify-content-between w-100 flex-wrap align-items-center mb-3">
            <div>
                <h1 class="h4">{{ __('holidays.holiday_calendar') }}</h1>
                <p class="text-muted">{{ __('holidays.manage_non_working_days') }}</p>
            </div>
            @if(Gate::allows('setting-read'))
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#CreateHolidayModal">
                {{ __('holidays.add_holiday') }}
            </button>
            @endif
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-4">
            <label>{{ __('companies.company') }}</label>
            <select wire:model.live="company_id" class="form-select">
                <option value="">{{ __('common.all') }}</option>
                @foreach($companies as $company)
                <option value="{{ $company->id }}">{{ $company->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>{{ __('holidays.date') }}</th>
                        <th>{{ __('holidays.name') }}</th>
                        <th>{{ __('holidays.description') }}</th>
                        <th>{{ __('companies.company') }}</th>
                        @if(Gate::allows('setting-read'))
                        <th>{{ __('common.action') }}</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($holidays as $holiday)
                    <tr>
                        <td>{{ $holiday->date->format('Y-m-d') }}</td>
                        <td>{{ $holiday->name }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($holiday->description, 50) }}</td>
                        <td>{{ $holiday->company?->name ?? __('holidays.global') }}</td>
                        @if(Gate::allows('setting-read'))
                        <td>
                            <a href="#" wire:click="initData({{ $holiday->id }})" data-bs-toggle="modal" data-bs-target="#EditHolidayModal" class="btn btn-sm btn-outline-primary">{{ __('common.edit') }}</a>
                            <a href="#" wire:click="initData({{ $holiday->id }})" data-bs-toggle="modal" data-bs-target="#DeleteHolidayModal" class="btn btn-sm btn-outline-danger">{{ __('common.delete') }}</a>
                        </td>
                        @endif
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-4">{{ __('holidays.no_holidays') }}</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3">{{ $holidays->links() }}</div>
    </div>

    @include('livewire.portal.holidays.create-modal')
    @include('livewire.portal.holidays.edit-modal')
    @include('livewire.portal.holidays.delete-modal')
</div>
