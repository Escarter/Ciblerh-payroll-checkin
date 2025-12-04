<div wire:ignore.self class="modal side-layout-modal fade" id="EditRoleModal" tabindex="-1" aria-labelledby="modal-form" data-bs-backdrop="static" data-bs-keyboard="false" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered " role="document" style="max-width:65%;">
        <div class="modal-content">
            <div class="modal-body p-0">
                <div class="p-3 p-lg-4">
                    <div class="mb-4 mt-md-0">
                        <h1 class="mb-0 h4">{{ __('roles.edit_role') }}</h1>
                        <p>{{ __('roles.update_role_modify_permissions') }} &#128522;</p>
                    </div>
                    @livewire('portal.roles.partial.edit')
                </div>
            </div>
        </div>
    </div>
</div>
