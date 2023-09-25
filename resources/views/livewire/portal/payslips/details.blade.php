<div>
    <div class='py-2 pb-2'>
        <div class="d-flex justify-content-between w-100 flex-wrap mb-4 align-items-center">
            <div class="mb-lg-0">
                <nav aria-label="breadcrumb" class="d-none d-md-inline-block">
                    <ol class="breadcrumb breadcrumb-dark breadcrumb-transparent">
                        <li class="breadcrumb-item"><a href="#"><svg class="icon icon-xxs" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                                </svg></a></li>
                        <li class="breadcrumb-item"><a href="/">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{route('portal.payslips.index')}}">{{__('Process Details')}}</a></li>
                        <li class="breadcrumb-item active" aria-current="page">{{__(ucfirst($job->department->name) .' Payslips')}}</li>
                    </ol>
                </nav>
                <h1 class="h4">
                    <svg class="icon me-2" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"></path> 
                    </svg>
                    {{__(ucfirst($job->department->name) .' Payslips Details')}}
                </h1>
                <p class="mb-0">{{__('Create new, update and delete or send payslip to any employee on within this group.')}}</p>
            </div>
        </div>
    </div>
    <div>
        <x-alert />
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
                            <th class="border-bottom">{{__('Email status')}}</th>
                            <th class="border-bottom">{{__('SMS status')}}</th>
                            <th class="border-bottom">{{__('Action')}}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($payslips as $payslip)
                        <tr>
                            <td>
                                @php
                                $url = '/admin/groups/'.$payslip->sendProcess->group_id.'/employees?employee_id='.$payslip->employee_id;
                                @endphp
                                <a href="{{$url}}" class="d-flex align-items-center">
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
                                @if($payslip->email_sent_status == 'successful')
                                <span class="badge badge-lg text-md bg-success">{{__('Succesful')}}</span>
                                @elseif($payslip->email_sent_status == 'failed' )
                                <span class="badge badge-lg text-md bg-danger">{{__('Failed')}}</span>
                                @else
                                <span class="badge badge-lg text-md text-gray bg-warning">{{__('Pending...')}}</span>
                                @endif
                            </td>
                            <td>
                                @if($payslip->sms_sent_status == 'successful')
                                <span class="badge badge-lg text-md bg-success">{{__('Succesful')}}</span>
                                @elseif($payslip->sms_sent_status == 'failed')
                                <span class="badge badge-lg text-md bg-danger">{{__('Failed')}}</span>
                                @else
                                <span class="badge badge-lg text-md text-dark bg-warning">{{__('Pending...')}}</span>
                                @endif
                            </td>
                            <td>
                                @if($payslip->email_sent_status == 'failed' || $payslip->email_sent_status == 'pending' )
                                <a href='#' data-id="{{$payslip->id}}" data-bs-url="/admin/payslips/edit/{{$payslip->id}}" data-bs-toggle="modal" data-bs-target="#resendPayslipModal">
                                    <svg class="icon icon-xs text-info" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                    </svg>
                                </a>
                                @endif
                                @if($payslip->sms_sent_status == 'failed' && $payslip->sms_sent_status == 'successful')
                                <a href='/admin/payslips/{{$payslip->id}}/resend-sms' class="mr-4">
                                    <svg class="icon icon-xs text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                                    </svg>
                                </a>
                                @endif

                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
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