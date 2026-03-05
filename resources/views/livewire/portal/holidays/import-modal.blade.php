<div wire:ignore.self class="modal fade" id="ImportHolidaysModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('holidays.bulk_import') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small">{{ __('holidays.bulk_import_help') }}</p>
                <div class="mb-3">
                    <label class="form-label">{{ __('holidays.import_file') }}</label>
                    <input type="file" wire:model="import_file" class="form-control @error('import_file') is-invalid @enderror" accept=".csv,.txt">
                    @error('import_file')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ __('companies.company') }}</label>
                    <select wire:model="company_id" class="form-select">
                        <option value="">{{ __('holidays.global') }}</option>
                        @foreach($companies ?? [] as $company)
                        <option value="{{ $company->id }}">{{ $company->name }}</option>
                        @endforeach
                    </select>
                    <small class="text-muted">{{ __('holidays.import_company_help') }}</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('common.close') }}</button>
                <button type="button" class="btn btn-primary" wire:click="importBulk" wire:loading.attr="disabled">
                    <span wire:loading.remove>{{ __('common.import') }}</span>
                    <span wire:loading>{{ __('import.importing') }}</span>
                </button>
            </div>
        </div>
    </div>
</div>
