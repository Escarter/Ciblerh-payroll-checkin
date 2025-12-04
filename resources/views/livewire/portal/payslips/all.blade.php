<div>
    @include('livewire.partials.delete-modal')
    @include('livewire.partials.bulk-delete-modal-generic', ['selectedItems' => $selectedJobs, 'itemType' => count($selectedJobs) === 1 ? __('payslip process') : __('payslip processes')])
    @include('livewire.partials.bulk-force-delete-modal-generic', ['selectedItems' => $selectedJobs, 'itemType' => count($selectedJobs) === 1 ? __('payslip process') : __('payslip processes')])
    @include('livewire.partials.force-delete-modal-generic', ['selectedItems' => $selectedJobs, 'itemType' => __('payslip process')])
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

    <div class='mb-3 mt-0'>
        <div class='row'>
            <div class="col-12 col-sm-6 col-xl-4 mb-2">
                <div class="card border-0 shadow">
                    <div class="card-body">
                        <div class="row d-block d-xl-flex align-items-center">
                            <div class="col-12 col-xl-4 text-xl-center mb-2 mb-xl-0 d-flex align-items-center justify-content-xl-center">
                                <div class="icon-shape icon-shape-primary rounded me-2 me-sm-0">
                                    <svg class="icon icon-md" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                                <div class="d-sm-none">
                                    <h2 class="fw-extrabold h5">{{__('Total Processes')}}</h2>
                                    <h3 class="mb-1">{{numberFormat($jobs_count ?? 0)}}</h3>
                                </div>
                            </div>
                            <div class="col-12 col-xl-8 px-xl-0">
                                <a href="#" class="d-none d-sm-block">
                                    <h2 class="h5">{{__('Total Processes')}}</h2>
                                    <h3 class="fw-extrabold mb-1">{{numberFormat($jobs_count ?? 0)}}</h3>
                                </a>
                                <div class="small d-flex mt-1">
                                    <div>{{ \Str::plural(__('Process'), $jobs_count ?? 0) }} {{__('in the system')}}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-4 mb-2">
                <div class="card border-0 shadow">
                    <div class="card-body">
                        <div class="row d-block d-xl-flex align-items-center">
                            <div class="col-12 col-xl-4 text-xl-center mb-2 mb-xl-0 d-flex align-items-center justify-content-xl-center">
                                <div class="icon-shape icon-shape-success rounded me-2 me-sm-0">
                                    <svg class="icon icon-md" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                                    </svg>
                                </div>
                                <div class="d-sm-none">
                                    <h2 class="fw-extrabold h5">{{ __(\Str::plural('Process', $active_jobs ?? 0)) }}</h2>
                                    <h3 class="mb-1">{{numberFormat($active_jobs ?? 0)}}</h3>
                                </div>
                            </div>
                            <div class="col-12 col-xl-8 px-xl-0">
                                <a href="#" class="d-none d-sm-block">
                                    <h2 class="h5">{{ __(\Str::plural('Process', $active_jobs ?? 0)) }}</h2>
                                    <h3 class="fw-extrabold mb-1">{{numberFormat($active_jobs ?? 0)}}</h3>
                                </a>
                                <div class="small d-flex mt-1">
                                    <div>{{ \Str::plural(__('Process'), $active_jobs ?? 0) }} {{__('that are active!')}}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-4 mb-2">
                <div class="card border-0 shadow">
                    <div class="card-body">
                        <div class="row d-block d-xl-flex align-items-center">
                            <div class="col-12 col-xl-4 text-xl-center mb-2 mb-xl-0 d-flex align-items-center justify-content-xl-center">
                                <div class="icon-shape icon-shape-danger rounded me-2 me-sm-0">
                                    <svg class="icon icon-md" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </div>
                                <div class="d-sm-none">
                                    <h2 class="fw-extrabold h5">{{ \Str::plural(__('Process'), $deleted_jobs ?? 0) }}</h2>
                                    <h3 class="mb-1">{{numberFormat($deleted_jobs ?? 0)}} </h3>
                                </div>
                            </div>
                            <div class="col-12 col-xl-8 px-xl-0">
                                <a href="#" class="d-none d-sm-block">
                                    <h2 class="h5">{{ \Str::plural(__('Process'), $deleted_jobs ?? 0) }}</h2>
                                    <h3 class="fw-extrabold mb-1">{{numberFormat($deleted_jobs ?? 0)}} </h3>
                                </a>
                                <div class="small d-flex mt-1">
                                    <div>{{ \Str::plural(__('Process'), $deleted_jobs ?? 0) }} {{__('that are deleted!')}}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <x-alert />

    <!-- Tabs and Bulk Actions -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <button class="btn {{ $activeTab === 'active' ? 'btn-primary' : 'btn-outline-primary' }}"
                wire:click="switchTab('active')"
                type="button">
                <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                </svg>
                {{__('common.active')}}
                <span class="badge {{ $activeTab === 'active' ? 'bg-light text-white' : 'bg-primary text-white' }} ms-1">{{ $active_jobs ?? 0 }}</span>
            </button>
            <button class="btn {{ $activeTab === 'deleted' ? 'btn-tertiary' : 'btn-outline-tertiary' }}"
                wire:click="switchTab('deleted')"
                type="button">
                <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
                {{__('common.deleted')}}
                <span class="badge {{ $activeTab === 'deleted' ? 'bg-light text-white' : 'bg-tertiary text-white' }} ms-1">{{ $deleted_jobs ?? 0 }}</span>
            </button>
        </div>
        <div>
            @if($activeTab === 'active')
            @if(count($selectedJobs) > 0)
            <button type="button" class="btn btn-sm btn-outline-danger me-2" data-bs-toggle="modal" data-bs-target="#bulkDeleteModal">
                <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
                {{__('common.move_to_trash')}}
                <span class="badge bg-danger text-white ms-1">{{ count($selectedJobs) }}</span>
            </button>
            <button wire:click="$set('selectedJobs', [])"
                class="btn btn-sm btn-outline-secondary">
                {{__('Clear Selection')}}
            </button>
            @endif
            @else
            @if(count($selectedJobs) > 0)
            <button type="button" class="btn btn-sm btn-outline-success me-2" data-bs-toggle="modal" data-bs-target="#bulkRestoreModal">
                <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                {{__('common.restore_selected')}}
                <span class="badge bg-success text-white ms-1">{{ count($selectedJobs) }}</span>
            </button>
            <button type="button" class="btn btn-sm btn-outline-danger me-2" data-bs-toggle="modal" data-bs-target="#bulkForceDeleteModal">
                <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
                {{__('common.delete_forever')}}
                <span class="badge bg-danger text-white ms-1">{{ count($selectedJobs) }}</span>
            </button>
            <button wire:click="$set('selectedJobs', [])"
                class="btn btn-sm btn-outline-secondary">
                {{__('Clear Selection')}}
            </button>
            @endif
            @endif
        </div>

    </div>

    <div class="card">
        @if(count($jobs)>0)

        <div class="table-responsive ">
            <table class="table user-table table-hover table-bordered align-items-center dataTable" id="datatable">
                <thead>
                    <tr>
                        <th class="border-bottom">
                            <input type="checkbox" class="form-check-input" wire:click="toggleSelectAll" {{ $selectAll ? 'checked' : '' }}>
                        </th>
                        <th class="border-bottom">{{__('departments.department')}}</th>
                        <th class="border-bottom">{{__('By')}}</th>
                        <th class="border-bottom">{{__('Details')}}</th>
                        <th class="border-bottom text-center">{{__('common.status')}}</th>
                        <th class="border-bottom text-center">{{__('common.action')}}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($jobs as $job)
                    <tr>
                        <td>
                            <input type="checkbox" class="form-check-input"
                                wire:click="toggleJobSelection({{ $job->id }})"
                                {{ in_array($job->id, $selectedJobs) ? 'checked' : '' }}>
                        </td>
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
                            <div class="d-flex flex-column">
                                <div class="mb-1">
                                    <span class="fw-bold text-primary">{{__('Target')}}:</span>
                                    <span class="ms-1">{{!is_null($job->department) ? count($job->department->employees) : 0 }} {{__('Employees')}}</span>
                                </div>
                                <div class="mb-1">
                                    <span class="fw-bold text-primary">{{__('Period')}}:</span>
                                    <span class="ms-1">{{$job->month}} - {{$job->year}}</span>
                                </div>
                                <div>
                                    <span class="fw-bold text-primary">{{__('Date Created')}}:</span>
                                    <span class="ms-1">{{$job->created_at->diffForHumans()}}</span>
                                </div>
                            </div>
                        </td>
                        <td class="text-center">
                            @if($job->status == 'successful')
                            <span class="badge badge-lg text-md bg-success">{{__('common.successful')}}</span>
                            @elseif($job->status == 'failed')
                            <span class="badge badge-lg text-md bg-danger">{{__('common.failed')}}</span>
                            @else
                            <span class="badge badge-lg text-md bg-warning">{{__('Processing...')}}</span>
                            @endif
                        </td>
                        <td>
                            @if($activeTab === 'active')
                            <!-- View Details Link -->
                            <a href="/portal/payslips/{{$job->id}}/details" wire:navigate class="text-primary me-2" title="{{__('View Details & Download Payslips')}}">
                                <svg class="icon icon-xs" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                            </a>
                            <!-- Delete Link -->
                            <a href='#' wire:click.prevent="initData({{$job->id}})" data-bs-toggle="modal" data-bs-target="#DeleteModal" class="text-danger" title="{{__('Delete')}}">
                                <svg class="icon icon-xs" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </a>
                            @else
                            <!-- Restore Link -->
                            <a href='#' wire:click="restore({{$job->id}})" class="text-success me-2" title="{{__('Restore')}}">
                                <svg class="icon icon-xs" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                            </a>
                            <!-- Force Delete Link -->
                            <a href='#' wire:click.prevent="$set('selectedJobs', [{{$job->id}}])" data-bs-toggle="modal" data-bs-target="#ForceDeleteModal" class="text-danger" title="{{__('common.delete_forever')}}">
                                <svg class="icon icon-xs" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </a>
                            @endif
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