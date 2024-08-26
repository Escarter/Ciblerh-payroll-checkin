<div>
    @include('livewire.portal.employees.payslip.resend-email-modal')
    @include('livewire.portal.employees.payslip.resend-sms-modal')
    <x-alert />
    <div>
        <div class='pt-2'>
            <div class="d-flex justify-content-between w-100 flex-wrap mb-4 align-items-center">
                <div class="mb-lg-0">
                    <nav aria-label="breadcrumb" class="d-none d-md-inline-block">
                        <ol class="breadcrumb breadcrumb-dark breadcrumb-transparent">
                            <li class="breadcrumb-item"><a href="#"><svg class="icon icon-xxs" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                                    </svg></a></li>
                            <li class="breadcrumb-item"><a href="/" wire:navigate>Home</a></li>
                            <li class="breadcrumb-item"><a href="{{route('portal.all-employees')}}" wire:navigate>{{__('Groups')}}</a></li>
                            <li class="breadcrumb-item active" aria-current="page">{{__(ucfirst($employee->name) .' Payslips')}}</li>
                        </ol>
                    </nav>
                    <h1 class="h4">
                        <svg class="icon me-2" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"></path> 
                        </svg>
                        {{__(ucfirst($employee->first_name) .' - Payslips history')}}
                    </h1>
                    <p class="mb-0">{{__('View all employee payslip history')}}</p>
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
            <div class="card">
                <div class="table-responsive py-4">
                    <table class="table employee-table table-hover align-items-center dataTable" id="datatable">
                        <thead>
                            <tr>
                                <th class="border-bottom">{{__('Name')}}</th>
                                <th class="border-bottom">{{__('Matricule')}}</th>
                                <th class="border-bottom">{{__('Contacts')}}</th>
                                <th class="border-bottom">{{__('Period')}}</th>
                                <th class="border-bottom">{{__('Category')}}</th>
                                <th class="border-bottom">{{__('When')}}</th>
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

                                    <a href="#" class="d-flex align-items-center">
                                        <div class="avatar d-flex align-items-center justify-content-center fw-bold rounded text-white bg-primary me-3"><span>{{$payslip->initials}}</span></div>
                                        <div class="d-block"><span class="fw-bold">{{$payslip->name}}</span>
                                            <div class="small text-gray">{{$payslip->email}}</div>
                                        </div>
                                    </a>
                                </td>
                                <td>
                                    <span class="fw-normal">{{$payslip->matricule}}</span>
                                </td>
                                <td>
                                    @if(!empty($payslip->phone))
                                    <a href='tel:{{$payslip->phone}}' class="small text-gray">
                                        <svg class="icon icon-xxs" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                        </svg>{{$payslip->phone}}
                                        <br>
                                    </a>
                                    @endif
                                </td>
                                <td>
                                    <span class="fw-normal">{{$payslip->month}} - {{$payslip->year}}</span>
                                </td>
                                <td>
                                    @if(empty($payslip->send_payslip_process_id))
                                    <span class="fw-normal text-gray-600"> {{__('single')}}</span>
                                    @else
                                    <span class="fw-normal text-info"> {{__('bulk')}}</span>
                                    @endif
                                    <br>
                                </td>
                                <td>
                                    <span class="fw-normal">{{$payslip->created_at}}</span>
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
                                    <a href='#' wire:click.prevent="initData({{$payslip->id}})" data-bs-toggle="modal" data-bs-target="#resendEmailModal">
                                        <svg class="icon icon-xs text-info" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                                        </svg>
                                    </a>
                                    <a href='#' wire:click.prevent="initData({{$payslip->id}})" data-bs-toggle="modal" data-bs-target="#resendSMSModal">
                                        <svg class="icon icon-xs text-tertiary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" />
                                        </svg>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="10">
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
        {{-- @include('admin.payslips.resend-payslip') --}}
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
</div>