 <div wire:ignore.self class="modal side-layout-modal fade" id="importDepartmentsModal" tabindex="-1" aria-labelledby="importdepartmentsModalLabel" aria-hidden="true">
     <div class="modal-dialog modal-dialog-centered modal-lg">
         <div class="modal-content">
             <div class="modal-body p-0">
                 <div class="p-3 p-lg-4">
                     <div class="mb-4 mt-md-0">
                         <h1 class="mb-0 h4">{{__('Import :name',['name'=>__('Departments')])}}</h1>
                         <p>{{__('Import new :name from excel file',['name'=>__('Departments')])}} &#128522;</p>

                     </div>
                     <x-form-items.form wire:submit="import" class="form-modal">
                         <p>{{__('Steps you have to follow for importing new :name',['name'=>__('departments')])}}</p>
                         <div class='mb-4'>
                             <ol>
                                 <li>{{__('Download sample :name import template',['name'=>__('Department')])}} <a href="{{asset('templates/import_departments.xlsx')}}">{{__('Template')}}</a></li>
                                 <li>{{__('Fill template with your :name data',['name'=>__('departments')])}}</li>
                                 <li>{{__('Upload the filled templated using below form and click on import button to import')}}</li>
                             </ol>
                         </div>

                         <div class="mb-4">
                             <label for="department_file" class="form-label">{{__('Select file')}}</label>
                             <input wire:model="department_file" class="form-control @error('department_file') is-invalid @enderror" type="file" name="department_file" id="formFile" required="">
                             @error('department_file')
                             <div class="invalid-feedback">{{$message}}</div>
                             @enderror
                         </div>
                         <div class="d-flex justify-content-end">
                             <button class="btn btn-gray-200 text-gray-600 ms-auto mx-3" type="button" data-bs-dismiss="modal">{{__('Close')}}</button>
                             <button class=" btn btn-primary" type="submit" wire:click.prevent="import" wire:loading.attr="disabled" {{empty($department_file) ? "disabled" : '' }}>{{__('Import')}}</button>
                         </div>
                     </x-form-items.form>
                 </div>
             </div>
         </div>
     </div>
 </div>