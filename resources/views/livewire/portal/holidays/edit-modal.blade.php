<div wire:ignore.self class="modal fade" id="EditHolidayModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('holidays.edit_holiday') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form wire:submit="update">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_date" class="form-label">{{ __('holidays.date') }}</label>
                        <input type="date" wire:model="date" class="form-control @error('date') is-invalid @enderror" id="edit_date">
                        @error('date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">{{ __('holidays.name') }}</label>
                        <input type="text" wire:model="name" class="form-control @error('name') is-invalid @enderror" id="edit_name">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">{{ __('holidays.description') }}</label>
                        <textarea wire:model="description" class="form-control" id="edit_description" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="edit_company_id" class="form-label">{{ __('companies.company') }}</label>
                        <select wire:model="company_id" class="form-select" id="edit_company_id">
                            <option value="">{{ __('holidays.global') }}</option>
                            @foreach($companies as $company)
                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('common.close') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('common.update') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
