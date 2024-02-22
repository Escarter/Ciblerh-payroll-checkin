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
                        <li class="breadcrumb-item"><a href="{{route('portal.payslips.index')}}" wire:navigate>{{__('Process Details')}}</a></li>
                        <li class="breadcrumb-item active" aria-current="page">{{__(ucfirst($job->department->name) .' Payslips')}}</li>
                    </ol>
                </nav>
                <h1 class="h4">
                    <svg class="icon me-2" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"></path> 
                    </svg>
                    {{__(ucfirst($job->department->name) .' Payslips Details')}}
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
        <div class="card">
            <div class="table-responsive py-4">
                <table class="table employee-table table-hover align-items-center dataTable" id="datatable">
                    <thead>
                        <tr>
                            <th class="border-bottom">{{__('Name')}}</th>
                            <th class="border-bottom">{{__('Matricule')}}</th>
                            <th class="border-bottom">{{__('Contacts')}}</th>
                            <th class="border-bottom">{{__('Period')}}</th>
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
                                <a href="{{ !empty($payslip->employee) ?  ($payslip->employee->getRoleNames()->first() === 'employee' ? route('portal.employee.payslips',['employee_uuid' => $payslip->employee->uuid]) : '#') : '#'}}" class="d-flex align-items-center">
                                    <div class="avatar d-flex align-items-center justify-content-center fw-bold rounded bg-primary text-white me-3"><span>{{$payslip->initials}}</span></div>
                                    <div class="d-block"><span class="fw-bold">{{$payslip->name}}</span>
                                        <div class="small text-gray">{{$payslip->email}}</div>
                                    </div>
                                </a>
                            </td>
                            <td>
                                <span class="fw-normal">{{$payslip->matricule}}</span>
                            </td>
                            <td>

                                @if(!is_null($payslip->phone))
                                <a href='tel:{{$payslip->phone}}' class="small text-gray">
                                    <svg class="icon icon-xxs" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                    </svg><span class="fw-normal"> {{$payslip->phone}}</span><br>
                                </a>
                                @endif
                            </td>
                            <td>
                                <span class="fw-normal">{{$payslip->month}} - {{$payslip->year}}</span>
                            </td>
                            <td>
                                <span class="fw-normal">{{$payslip->created_at->diffForHumans()}}</span>
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
                                @if($payslip->email_sent_status == 2 || $payslip->email_sent_status == 0 )
                                <a href='#' data-id="{{$payslip->id}}" data-bs-url="/admin/payslips/edit/{{$payslip->id}}" data-bs-toggle="modal" data-bs-target="#resendPayslipModal">
                                    <svg class="icon icon-xs text-warning" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                                    </svg>
                                </a>
                                @endif
                                @if($payslip->sms_sent_status == 2 && $payslip->sms_sent_status == 0)
                                <a href='/admin/payslips/{{$payslip->id}}/resend-sms' class="mr-4">
                                    <svg class="icon icon-xs text-warning" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                                    </svg>
                                </a>
                                @endif

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
                <div class='d-flex justify-content-between align-items-center pt-3 px-3 '>
                    <div>
                        {{__('Showing')}} {{$perPage > $payslips_count ? $payslips_count : $perPage  }} {{__('items of')}} {{$payslips_count}}
                    </div>
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