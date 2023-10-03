<div wire:ignore.self class="modal side-layout-modal fade" id="EditServiceModal"  tabindex="-1" aria-labelledby="modal-form" data-bs-backdrop="static" data-bs-keyboard="false" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered " role="document">
        <div class="modal-content">
            <div class="modal-body p-0">
                <div class="p-3 p-lg-4">
                    <div class="mb-4 mt-md-0">
                        <h1 class="mb-0 h4">{{__('Service d\'édition')}}</h1>
                        <p>{{__('Modifier les détails du service')}} &#128522;</p>
                    </div>
                    <x-form-items.form wire:submit="update">
                        <div class="form-group row mb-2">
                            <div class='col-md-6'>
                                <label for="name">{{__('Nom')}}</label>
                                <input wire:model="name" type="text" class="form-control  @error('name') is-invalid @enderror" required="" name="name">
                                @error('name')
                                <div class="invalid-feedback">{{$message}}</div>
                                @enderror
                            </div>
                            <div class='col-md-6'>
                                <label for="code">{{__('Code')}}</label>
                                <input wire:model="code" type="text" class="form-control  @error('code') is-invalid @enderror" required="" value="" name="code">
                                @error('code')
                                <div class="invalid-feedback">{{$message}}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="form-group mb-4">
                            <label for="description">{{__('Description')}}</label>
                            <textarea wire:model="description" name="description" class="form-control  @error('description') is-invalid @enderror" id='' cols='3' rows='3'></textarea>
                            @error('description')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="button"  wire:click.prevent="clearFields"  class="btn btn-light text-600 ms-auto mx-3" data-bs-dismiss="modal">{{__('Fermer')}}</button>
                            <button type="submit" wire:click.prevent="update" class="btn btn-primary btn-sm btn-loading">{{__('Mettre à jour')}}</button>
                        </div>
                    </x-form-items.form>
                </div>
            </div>
        </div>
    </div>
</div>
