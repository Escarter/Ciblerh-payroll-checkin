<div>
    @include('livewire.portal.payslips.partials.resend-payslip')
    @include('livewire.partials.delete-modal')
    @include('livewire.partials.force-delete-modal')
    @include('livewire.partials.bulk-delete-modal-generic', ['selectedItems' => $selectedPayslips, 'itemType' => count($selectedPayslips) === 1 ? __('payslip') : __('payslips')])
    @include('livewire.partials.bulk-force-delete-modal-generic', ['selectedItems' => $selectedPayslips, 'itemType' => count($selectedPayslips) === 1 ? __('payslip') : __('payslips')])
    <x-alert />
    <div class='pt-2'>
        <div class="d-flex justify-content-between w-100 flex-wrap mb-4 align-items-center">
            <div class="mb-lg-0">
                <nav aria-label="breadcrumb" class="d-none d-md-inline-block">
                    <ol class="breadcrumb breadcrumb-dark breadcrumb-transparent">
                        <li class="breadcrumb-item"><a href="#"><svg class="icon icon-xxs" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                                </svg></a></li>
                        <li class="breadcrumb-item"><a href="/" wire:navigate>Home</a></li>
                        <li class="breadcrumb-item"><a href="{{route('portal.payslips.index')}}" wire:navigate>{{__('Process Details')}}</a></li>
                        <li class="breadcrumb-item active" aria-current="page">{{!empty($job->department) ? ucfirst($job->department->name) .__(' Payslips') : __('Payslips')}}</li>
                    </ol>
                </nav>
                <h1 class="h4">
                    <svg class="icon me-2" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"></path> 
                    </svg>
                    {{!empty($job->department) ? ucfirst($job->department->name) .__(' Payslips Details') : __('Payslips Details')}}
                </h1>
                <p class="mb-0">{{__('Status of Payslips sending!!!!')}}</p>
            </div>
        </div>
    </div>
    <div>
        <x-alert />
        <div class="row pb-3">
            <div class="col-md-3">
                <label for="search">{{__('Search')}}: </label>
                <input wire:model.live="query" id="search" type="text" placeholder="{{__('Search...')}}" class="form-control">
                <p class="badge badge-info" wire:model.live="resultCount">{{$resultCount}}</p>
            </div>
            <div class="col-md-3">
                <label for="orderBy">{{__('Order By')}}: </label>
                <select wire:model.live="orderBy" id="orderBy" class="form-select">
                    <option value="first_name">{{__('First Name')}}</option>
                    <option value="last_name">{{__('Last Name')}}</option>
                    <option value="created_at">{{__('Created Date')}}</option>
                </select>
            </div>

            <div class="col-md-3">
                <label for="direction">{{__('Order direction')}}: </label>
                <select wire:model.live="orderAsc" id="direction" class="form-select">
                    <option value="asc">{{__('Ascending')}}</option>
                    <option value="desc">{{__('Descending')}}</option>
                </select>
            </div>

            <div class="col-md-3">
                <label for="perPage">{{__('Items Per Page')}}: </label>
                <select wire:model.live="perPage" id="perPage" class="form-select">
                    <option value="5">5</option>
                    <option value="10">10</option>
                    <option value="15">15</option>
                    <option value="20">20</option>
                    <option value="25">25</option>
                </select>
            </div>
        </div>

        <!-- Table Controls: Bulk Actions (Left) + Tab Buttons (Right) -->
        <div class="d-flex justify-content-between align-items-center mb-3">

            <!-- Tab Buttons (Right) -->
            <div class="d-flex gap-2">
                <button class="btn {{ $activeTab === 'active' ? 'btn-primary' : 'btn-outline-primary' }}"
                    wire:click="switchTab('active')"
                    type="button">
                    <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    {{__('Active')}}
                    <span class="badge {{ $activeTab === 'active' ? 'bg-light text-white' : 'bg-primary text-white' }} ms-1">{{ $active_payslips ?? 0 }}</span>
                </button>

                <button class="btn {{ $activeTab === 'deleted' ? 'btn-tertiary' : 'btn-outline-tertiary' }}"
                    wire:click="switchTab('deleted')"
                    type="button">
                    <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    {{__('Deleted')}}
                    <span class="badge {{ $activeTab === 'deleted' ? 'bg-light text-white' : 'bg-tertiary text-white' }} ms-1">{{ $deleted_payslips ?? 0 }}</span>
                </button>
            </div>

            <!-- Bulk Actions (Left) -->
            <div>
                @if($activeTab === 'active')
                    <!-- Soft Delete Bulk Actions (when items selected for delete) -->
                    @if(count($selectedPayslips) > 0)
                    <div class="d-flex align-items-center gap-2">
                        @can('payslip-delete')
                        <button type="button"
                            class="btn btn-sm btn-outline-danger d-flex align-items-center"
                            title="{{ __('Move Selected Payslips to Trash') }}"
                            data-bs-toggle="modal" 
                            data-bs-target="#BulkDeleteModal">
                            <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                            {{__('Move to Trash')}}
                            <span class="badge bg-danger text-white ms-1">{{ count($selectedPayslips) }}</span>
                        </button>
                        @endcan

                        <button wire:click="$set('selectedPayslips', [])"
                            class="btn btn-sm btn-outline-secondary d-flex align-items-center">
                            <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            {{__('Clear')}}
                        </button>
                    </div>
                    @endif
                @else
                    <!-- Deleted Tab Bulk Actions -->
                    @if(count($selectedPayslips) > 0)
                    <div class="d-flex align-items-center gap-2">
                        @can('payslip-delete')
                        <button wire:click="bulkRestore"
                            class="btn btn-sm btn-outline-success d-flex align-items-center me-2"
                            title="{{ __('Restore Selected Payslips') }}">
                            <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            {{__('Restore Selected')}}
                            <span class="badge bg-success text-white ms-1">{{ count($selectedPayslips) }}</span>
                        </button>

                        <button type="button"
                            class="btn btn-sm btn-outline-danger d-flex align-items-center"
                            title="{{ __('Permanently Delete Selected Payslips') }}"
                            data-bs-toggle="modal" 
                            data-bs-target="#BulkForceDeleteModal">
                            <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                            {{__('Delete Forever')}}
                            <span class="badge bg-danger text-white ms-1">{{ count($selectedPayslips) }}</span>
                        </button>
                        @endcan

                        <button wire:click="$set('selectedPayslips', [])"
                            class="btn btn-sm btn-outline-secondary d-flex align-items-center">
                            <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            {{__('Clear')}}
                        </button>
                    </div>
                    @endif
                @endif
            </div>
        </div>

        <div class="card">
            <div class="table-responsive pb-4">
                <table class="table employee-table table-bordered table-hover align-items-center dataTable" id="datatable">
                    <thead>
                        <tr>
                            <th class="border-bottom">
                                <div class="form-check d-flex justify-content-center align-items-center">
                                    <input class="form-check-input p-2" 
                                        wire:model.live="selectAll" 
                                        type="checkbox"
                                        wire:click="toggleSelectAll">
                                </div>
                            </th>
                            <th class="border-bottom">{{__('Name')}}</th>
                            <th class="border-bottom">{{__('Employee Info')}}</th>
                            <th class="border-bottom">{{__('Timeline')}}</th>
                            <th class="border-bottom">{{__('Encryption status')}}</th>
                            <th class="border-bottom">{{__('Email status')}}</th>
                            <th class="border-bottom">{{__('SMS status')}}</th>
                            <th class="border-bottom">{{__('Action')}}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payslips as $payslip)
                        <tr>
                            <td>
                                <div class="form-check d-flex justify-content-center align-items-center">
                                    <input class="form-check-input" 
                                        type="checkbox"
                                        wire:click="togglePayslipSelection({{ $payslip->id }})"
                                        {{ in_array($payslip->id, $selectedPayslips) ? 'checked' : '' }}>
                                </div>
                            </td>
                            <td>
                                <a href="{{ !empty($payslip->employee) ?  ($payslip->employee->getRoleNames()->first() === 'employee' ? route('portal.employee.payslips',['employee_uuid' => $payslip->employee->uuid]) : '#') : '#'}}" class="d-flex align-items-center">
                                    <div class="avatar d-flex align-items-center justify-content-center fw-bold rounded bg-primary text-white me-3"><span>{{$payslip->initials}}</span></div>
                                    <div class="d-block"><span class="fw-bold">{{$payslip->name}}</span>
                                        <div class="small text-gray">{{$payslip->email}}</div>
                                    </div>
                                </a>
                            </td>
                            <td>
                                <div class="d-flex flex-column">
                                    <div class="mb-1">
                                        <span class="fw-bold text-primary">{{__('Matricule')}}:</span>
                                        <span class="ms-1 fw-normal">{{$payslip->matricule}}</span>
                                    </div>
                                    @if(!is_null($payslip->phone))
                                    <div>
                                        <span class="fw-bold text-primary">{{__('Phone')}}:</span>
                                        <a href='tel:{{$payslip->phone}}' class="ms-1 small text-gray">
                                            <svg class="icon icon-xxs" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                            </svg>
                                            <span class="fw-normal">{{$payslip->phone}}</span>
                                        </a>
                                    </div>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="d-flex flex-column">
                                    <div class="mb-1">
                                        <span class="fw-bold text-primary">{{__('Period')}}:</span>
                                        <span class="ms-1 fw-normal">{{$payslip->month}} - {{$payslip->year}}</span>
                                    </div>
                                    <div>
                                        <span class="fw-bold text-primary">{{__('Created')}}:</span>
                                        <span class="ms-1 fw-normal">{{$payslip->created_at->diffForHumans()}}</span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if($payslip->encryption_status == 1)
                                <span class="badge badge-lg text-md bg-success">{{__('Succesful')}}</span>
                                @elseif($payslip->encryption_status == 2 )
                                <span class="badge badge-lg text-md bg-danger">{{__('Failed')}}</span>
                                @else
                                <span class="badge badge-lg text-md text-white bg-gray-400">{{__('Not Recorded')}}</span>
                                @endif
                            </td>
                            <td>
                                @if($payslip->email_sent_status == 1)
                                <span class="badge badge-lg text-md bg-success">{{__('Succesful')}}</span>
                                @elseif($payslip->email_sent_status == 2 )
                                <span class="badge badge-lg text-md bg-danger">{{__('Failed')}}</span>
                                @else
                                <span class="badge badge-lg text-md text-gray bg-warning">{{__('Pending...')}}</span>
                                @endif
                            </td>
                            <td>
                                @if($payslip->sms_sent_status == 1)
                                <span class="badge badge-lg text-md bg-success">{{__('Succesful')}}</span>
                                @elseif($payslip->sms_sent_status == 2)
                                <span class="badge badge-lg text-md bg-danger">{{__('Failed')}}</span>
                                @else
                                <span class="badge badge-lg text-md text-dark bg-warning">{{__('Pending...')}}</span>
                                @endif
                            </td>
                            <td>
                                @if($activeTab === 'active')
                                    <!-- Download Link -->
                                    <a href="#" wire:click="downloadPayslip({{$payslip->id}})" class="text-primary me-2" title="{{__('Download Payslip')}}">
                                        <svg class="icon icon-xs" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                    </a>
                                    <!-- Resend Link -->
                                    <a href='#' wire:click.prevent="initData({{$payslip->id}})" data-bs-toggle="modal" data-bs-target="#resendPayslipModal" class="text-warning me-2" title="{{__('Resend Payslip')}}">
                                        <svg class="icon icon-xs" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                                        </svg>
                                    </a>
                                    @can('payslip-delete')
                                    <!-- Delete Link -->
                                    <a href="#" wire:click.prevent="initData({{ $payslip->id }})" data-bs-toggle="modal" data-bs-target="#DeleteModal" class="text-danger" title="{{__('Delete')}}">
                                        <svg class="icon icon-xs" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </a>
                                    @endcan
                                @else
                                    @can('payslip-delete')
                                    <!-- Restore Link -->
                                    <a href="#" wire:click="restore({{ $payslip->id }})" class="text-success me-2" title="{{__('Restore')}}">
                                        <svg class="icon icon-xs" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                        </svg>
                                    </a>
                                    <!-- Force Delete Link -->
                                    <a href="#" wire:click.prevent="initData({{ $payslip->id }})" data-bs-toggle="modal" data-bs-target="#ForceDeleteModal" class="text-danger" title="{{__('Delete Forever')}}">
                                        <svg class="icon icon-xs" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </a>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9">
                                <div class="text-center text-gray-800 mt-2">
                                    <h4 class="fs-4 fw-bold">{{__('Opps nothing here')}} &#128540;</h4>
                                    <p>{{__('No Record Found..!')}}</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
                <div class='pt-3 px-3 '>
                    {{ $payslips->links() }}
                </div>
            </div>
        </div>
    </div>

    @section('scripts')
    <script>
        var resendPayslipModal = document.getElementById('resendPayslipModal')
        resendPayslipModal.addEventListener('show.bs.modal', function(event) {
            // Button that triggered the modal
            var button = event.relatedTarget
            // Extract info from data-bs-* attributes
            var url = button.getAttribute('data-bs-url')

            console.log(url);

            $.ajax({
                dataType: 'JSON',
                type: 'GET',
                url: url,
                success: function(response) {
                    console.log(response.data)

                    if (response.status == 'success') {
                        resendPayslipModal.querySelector('input[name="id"]').value = response.data.id
                        resendPayslipModal.querySelector('input[name="employee_id"]').value = response.data.employee_id
                        resendPayslipModal.querySelector('input[name="user"]').value = response.data.first_name + "" + response.data.last_name + " - " + response.data.matricule
                        resendPayslipModal.querySelector('input[name="month"]').value = response.data.month
                        resendPayslipModal.querySelector('input[name="year"]').value = response.data.year

                    }
                    if (response.status == 'error') {
                        toastr.warning(response.data, "@lang('Oops Something is not alright')");
                    }
                }
            });
        })
    </script>
    @endsection
</div>