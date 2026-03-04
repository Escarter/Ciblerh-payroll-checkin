<div wire:ignore.self class="modal fade" id="DeleteHolidayModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('holidays.delete_holiday') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>{{ __('holidays.delete_confirm') }}</p>
                @if($holiday)
                <p><strong>{{ $holiday->name }}</strong> - {{ $holiday->date->format('Y-m-d') }}</p>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                <button type="button" class="btn btn-danger" wire:click="delete">{{ __('common.delete') }}</button>
            </div>
        </div>
    </div>
</div>
