 <div wire:ignore.self class="modal side-layout-modal fade" id="importCompaniesModal" tabindex="-1" aria-labelledby="importCompaniesModalLabel" aria-hidden="true">
     <div class="modal-dialog modal-dialog-centered modal-lg">
         <div class="modal-content">
             <div class="modal-body p-0">
                 <div class="p-3 p-lg-4">
                     <div class="mb-4 mt-md-0">
                         <h1 class="mb-0 h4">{{__('Import :name',['name'=>__('Companies')])}}</h1>
                         <p>{{__('Import new :name from excel file',['name'=>__('Companies')])}} &#128522;</p>
                     </div>
                     <x-form-items.form wire:submit="import" class="form-modal">
                         <p>{{__('Steps you have to follow for importing new :name',['name'=>__('companies')])}}</p>
                         <div class='mb-4'>
                             <ol>
                                 <li>{{__('Download sample :name import template',['name'=>__('Company')])}} <a href="{{asset('templates/import_companies.xlsx')}}">{{__('Template')}}</a></li>
                                 <li>{{__('Fill template with your :name data',['name'=>__('Companies')])}}</li>
                                 <li>{{__('Upload the filled templated using below form and click on import button to import')}}</li>
                             </ol>
                         </div>
                         <div class="mb-4">
                             <label for="company_file" class="form-label">{{__('Select file')}}</label>
                             <input wire:model="company_file" class="form-control @error('company_file') is-invalid @enderror" type="file" name="company_file" id="formFile" required="">
                             @error('company_file')
                             <div class="invalid-feedback">{{$message}}</div>
                             @enderror
                         </div>
                         <div class="d-flex justify-content-end">
                             <button class="btn btn-gray-200 text-gray-600 ms-auto mx-3" type="button" data-bs-dismiss="modal">{{__('Close')}}</button>
                             <button class="btn btn-priamry" type="submit" wire:click.prevent="import" wire:loading.attr="disabled" {{empty($company_file) ? "disabled" : '' }}>{{__('Import')}}</button>
                         </div>
                     </x-form-items.form>
                 </div>
             </div>
         </div>
     </div>
 </div>