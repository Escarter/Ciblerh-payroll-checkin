 <div wire:ignore.self class="modal side-layout-modal fade" id="importTypesModal" tabindex="-1" aria-labelledby="importTypesModalLabel" aria-hidden="true">
     <div class="modal-dialog modal-dialog-centered modal-lg">
         <div class="modal-content">
             <div class="modal-body p-0">
                 <div class="p-3 p-lg-4">
                     <div class="mb-4 mt-md-0">
                         <h1 class="mb-0 h4">{{__('Import :name',['name'=>__('Types')])}}</h1>
                         <p>{{__('Import new :name from excel file',['name'=>__('Types')])}} &#128522;</p>
                     </div>
                     <x-form-items.form wire:submit="import" class="form-modal">
                         <p>{{__('Steps you have to follow for importing new :name',['name'=>__('Types')])}}</p>
                         <div class='mb-4'>
                             <ol>
                                 <li>{{__('Download sample :name import template',['name'=>__('Type')])}} <a href="{{asset('templates/import_types.xlsx')}}">{{__('Template')}}</a></li>
                                 <li>{{__('Fill template with your :name data',['name'=>__('Types')])}}</li>
                                 <li>{{__('Upload the filled templated using below form and click on import button to import')}}</li>
                             </ol>
                         </div>
                         <div class="mb-4">
                             <label for="type_file" class="form-label">{{__('Select file')}}</label>
                             <input wire:model="type_file" class="form-control @error('type_file') is-invalid @enderror" type="file" name="type_file" id="formFile" required="">
                             @error('type_file')
                             <div class="invalid-feedback">{{$message}}</div>
                             @enderror
                         </div>
                         <div class="d-flex justify-content-end">
                             <button class="btn btn-gray-200 text-gray-600 ms-auto mx-3" type="button" data-bs-dismiss="modal">{{__('Close')}}</button>
                             <button class=" btn btn-primary" type="submit" wire:click.prevent="import" wire:loading.attr="disabled" {{empty($type_file) ? "disabled" : '' }}>{{__('Import')}}</button>
                         </div>
                     </x-form-items.form>
                 </div>
             </div>
         </div>
     </div>
 </div>