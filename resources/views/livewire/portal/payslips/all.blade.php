<div>
    @include('livewire.partials.delete-modal')
    <x-alert />
    <div class='py-2 pb-2'>
        <div class="d-flex justify-content-between w-100 flex-wrap mb-4 align-items-center">
            <div class="mb-lg-0">
                <nav aria-label="breadcrumb" class="d-none d-md-inline-block">
                    <ol class="breadcrumb breadcrumb-dark breadcrumb-transparent">
                        <li class="breadcrumb-item"><a href="#"><svg class="icon icon-xxs" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                                </svg></a></li>
                        <li class="breadcrumb-item"><a href="{{route('portal.dashboard')}}" wire:navigate>Home</a></li>
                        <li class="breadcrumb-item"><a href="{{route('portal.payslips.index')}}" wire:navigate>{{__('Process Payslip')}}</a></li>
                        <li class="breadcrumb-item active" aria-current="page">{{__('Payslips Processed')}}</li>
                    </ol>
                </nav>
                <h1 class="h4">
                    <svg class="icon me-1" fill="none" stroke="currentColor" viewBox="0 0 25 25" xmlns="http://www.w3.org/2000/svg">
                        <path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                    {{__('All Payslips Processed')}}
                </h1>
                <p class="mb-0">{{__('View all payslips processed')}}</p>
            </div>
        </div>
    </div>
    <div class="card">
        @if(count($jobs)>0)
        <div class="table-responsive py-4">
            <table class="table user-table table-hover align-items-center dataTable" id="datatable">
                <thead>
                    <tr>
                        <th class="border-bottom">{{__('Department')}}</th>
                        <th class="border-bottom">{{__('By')}}</th>
                        <th class="border-bottom">{{__('Target')}}</th>
                        <th class="border-bottom">{{__('Period')}}</th>
                        <th class="border-bottom">{{__('Date Created')}}</th>
                        <th class="border-bottom text-center">{{__('Status')}}</th>
                        <th class="border-bottom text-center">{{__('Delete')}}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($jobs as $job)
                    <tr>
                        <td>
                            @if (!is_null($job->department))
                            <a href="/portal/payslips/{{$job->id}}/details" wire:navigate class="d-flex align-items-center">
                                <div class="avatar d-flex align-items-center justify-content-center fw-bold rounded bg-primary text-white me-3"><span>{{initials($job->department)}}</span></div>
                                <div class="d-block"><span class="fw-bold">{{$job->department->name}}</span>
                                    @hasanyrole('admin')
                                    <div class="small text-gray">
                                        <span class="d-flex align-items-baseline">
                                            <svg class="icon icon-xxs small me-1" fill="currentColor" stroke="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                            </svg>
                                            {{!is_null($job->owner) ? $job->owner->first_name : ''}}
                                        </span>
                                    </div>
                                    @endhasanyrole
                                </div>
                            </a>
                            @else
                            <p>{{__('Department deleted!')}}</p>
                            @endif
                        </td>
                        <td>
                            <div class="small text-gray">
                                <span class="d-flex align-items-baseline">
                                    <svg class="icon icon-xxs small me-1" fill="currentColor" stroke="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                    {{!is_null($job->owner) ? $job->owner->first_name : ''}}
                                </span>
                            </div>
                        </td>
                        <td>
                            <span>{{!is_null($job->department) ? count($job->department->employees) : 0 }} - {{__('Employees')}}</span>
                        </td>
                        <td>
                            <span>{{$job->month}} - {{$job->year}} </span>
                        </td>
                        <td>
                            <span>{{$job->created_at->diffForHumans()}}</span>
                        </td>
                        <td class="text-center">
                            @if($job->status == 'successful')
                            <span class="badge badge-lg text-md bg-success">{{__('Succesful')}}</span>
                            @elseif($job->status == 'failed')
                            <span class="badge badge-lg text-md bg-danger">{{__('Failed')}}</span>
                            @else
                            <span class="badge badge-lg text-md bg-warning">{{__('Processing...')}}</span>
                            @endif
                        </td>
                        <td>
                            <a href='#' wire:click.prevent="initData({{$job->id}})" data-bs-toggle="modal" data-bs-target="#DeleteModal">
                                <svg class="icon icon-xs text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div class='pt-3 px-3 '>
                {{ $jobs->links() }}
            </div>
        </div>
        @else
        <div class='p-5 text-center '>
            <img src="{{asset('img/empty.svg')}}" alt='' class="w-25 h-25">
            <p class="py-4 h5 text-muted">{{__('Start processing payslip to see the outcome here')}}</p>
        </div>
        @endif
    </div>
</div>