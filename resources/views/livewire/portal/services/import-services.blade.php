 <div wire:ignore.self class="modal side-layout-modal fade" id="importServicesModal" tabindex="-1" aria-labelledby="importservicesModalLabel" aria-hidden="true">
     <div class="modal-dialog modal-dialog-centered modal-lg">
         <div class="modal-content">
             <div class="modal-body p-0">
                 <div class="p-3 p-lg-4">
                     <div class="mb-4 mt-md-0">
                         <h1 class="mb-0 h4">{{__('Import :name',['name'=>__('Services')])}}</h1>
                         <p>{{__('Import new :name from excel file',['name'=>__('Services')])}} &#128522;</p>
                     </div>
                     <x-form-items.form wire:submit="import" class="form-modal">
                         <p>{{__('Steps you have to follow for importing new :name',['name'=>__('services')])}}</p>
                         <div class='mb-4'>
                            <ol>
                                <li>{{__('Download sample :name import template',['name'=>__('employees.service')])}} 
                                    <a href="{{asset('templates/import_services.xlsx')}}" class="btn btn-sm btn-outline-success ms-2" download>
                                        <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        {{__('common.download_template')}}
                                    </a>
                                </li>
                                <li>{{__('Fill template with your :name data',['name'=>__('services')])}}</li>
                                <li>{{__('common.upload_filled_template')}}</li>
                            </ol>
                         </div>
                         <div class="mb-4">
                             <label for="service_file" class="form-label">{{__('Select file')}}</label>
                             <input wire:model="service_file" class="form-control @error('service_file') is-invalid @enderror" type="file" name="service_file" id="formFile" required="">
                             @error('service_file')
                             <div class="invalid-feedback">{{$message}}</div>
                             @enderror
                         </div>
                         <div class="d-flex justify-content-end">
                             <button class="btn btn-gray-200 text-gray-600 ms-auto mx-3" type="button" data-bs-dismiss="modal">{{__('common.close')}}</button>
                             <button class=" btn btn-primary" type="submit" wire:click.prevent="import" wire:loading.attr="disabled" {{empty($service_file) ? "disabled" : '' }}>{{__('common.import')}}</button>
                         </div>
                     </x-form-items.form>
                 </div>
             </div>
         </div>
     </div>
 </div>