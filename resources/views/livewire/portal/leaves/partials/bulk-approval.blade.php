<div wire:ignore.self class="modal side-layout-modal fade" id="EditBulkChecklogModal" tabindex="-1" aria-labelledby="modal-form" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered " role="document">
        <div class="modal-content">
            <div class="modal-body p-0">
                <div class="p-3 p-lg-4">
                    @if($role =="supervisor")
                    @include('livewire.portal.leaves.partials.supervisor')
                    @else 
                    @include('livewire.portal.leaves.partials.manager')
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>