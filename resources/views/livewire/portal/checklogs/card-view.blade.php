{{-- Card View for Checkin Approval - Employee Grouped Display --}}
<div class="row g-3">
    @forelse($checklogs as $checklog)
    @if(!empty($checklog->user))
    <div class="col-12 col-md-6 col-lg-4">
        <div class="card h-100 shadow-sm border-0 hover-shadow transition-all">
            <!-- Header: Employee Info -->
            <div class="card-header bg-gradient-light border-bottom-2" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <div class="avatar-md d-flex align-items-center justify-content-center fw-bold fs-5 rounded bg-white text-primary">
                            {{$checklog->user->initials}}
                        </div>
                        <div class="text-white">
                            <h6 class="mb-0 fw-bold">{{ucwords($checklog->user_full_name)}}</h6>
                            <small class="opacity-75">{{$checklog->company_name}}</small>
                        </div>
                    </div>
                    <div class="form-check" style="margin-right: 0;">
                        @if($activeTab === 'active')
                        <input class="form-check-input" wire:model.live="selectedChecklogs" value="{{$checklog->id}}" type="checkbox">
                        @else
                        <input class="form-check-input"
                            type="checkbox"
                            wire:click="toggleChecklogSelectionForDelete({{ $checklog->id }})"
                            {{ in_array($checklog->id, $selectedChecklogsForDelete) ? 'checked' : '' }}>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Body: CheckIn Details -->
            <div class="card-body">
                <!-- Check Period -->
                <div class="mb-3">
                    <label class="form-label text-muted fw-bold small">{{__('employees.check_period')}}</label>
                    <div class="d-flex gap-2 flex-column">
                        <div class="d-flex align-items-center gap-2">
                            <svg class="icon icon-xs text-success" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm3.5-9c.83 0 1.5-.67 1.5-1.5S16.33 8 15.5 8 14 8.67 14 9.5s.67 1.5 1.5 1.5zm-7 0c.83 0 1.5-.67 1.5-1.5S9.33 8 8.5 8 7 8.67 7 9.5 7.67 11 8.5 11zm3.5 6.5c2.33 0 4.31-1.46 5.11-3.5H6.89c.8 2.04 2.78 3.5 5.11 3.5z"/>
                            </svg>
                            <span class="fw-500">{{$checklog->start_time}}</span>
                        </div>
                        @if($checklog->end_time)
                        <div class="d-flex align-items-center gap-2">
                            <svg class="icon icon-xs text-danger" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm3.5-9c.83 0 1.5-.67 1.5-1.5S16.33 8 15.5 8 14 8.67 14 9.5s.67 1.5 1.5 1.5zm-7 0c.83 0 1.5-.67 1.5-1.5S9.33 8 8.5 8 7 8.67 7 9.5 7.67 11 8.5 11zm3.5 6.5c2.33 0 4.31-1.46 5.11-3.5H6.89c.8 2.04 2.78 3.5 5.11 3.5z"/>
                            </svg>
                            <span class="fw-500">{{$checklog->end_time}}</span>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Hours Worked -->
                <div class="mb-3 p-2 bg-light rounded">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted fw-bold small">{{__('overtime.hours_worked')}}</span>
                        <span class="badge bg-primary fs-6">{{$checklog->time_worked}}</span>
                    </div>
                </div>

                <!-- Department & Service -->
                <div class="mb-3">
                    <small class="text-muted">
                        <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5.581m0 0H9m0 0h-.581m0 0H3m2 0h2.9m4.1 0H15m6 0v-2c0-.82-.185-1.602-.534-2.293M15 7h6m0 0v2m0-2v-2m0 2h-1.172M15 7c0-.82.185-1.602.534-2.293M15 7h1.172"></path>
                        </svg>
                        {{$checklog->department_name}}
                    </small>
                    @if($checklog->service_name)
                    <small class="d-block text-muted">
                        <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                        </svg>
                        {{$checklog->service_name}}
                    </small>
                    @endif
                </div>
            </div>

            <!-- Status Row: Supervisory & Manager Approvals -->
            <div class="card-footer bg-light border-top">
                <div class="d-flex gap-2 justify-content-between align-items-center mb-3">
                    <!-- Supervisor Approval -->
                    <div class="text-center flex-grow-1">
                        <small class="text-muted fw-bold d-block mb-1">{{__('common.sup_approval')}}</small>
                        @php
                            $supStatus = $checklog->supervisor_approval_status;
                            $supColor = $supStatus === 1 ? 'success' : ($supStatus === 2 ? 'danger' : 'warning');
                            $supText = $supStatus === 1 ? __('common.approve') : ($supStatus === 2 ? __('common.reject') : __('common.pending'));
                        @endphp
                        <span class="badge bg-{{ $supColor }} w-100 py-2">{{ $supText }}</span>
                    </div>

                    <!-- Manager Approval -->
                    <div class="text-center flex-grow-1">
                        <small class="text-muted fw-bold d-block mb-1">{{__('common.mgr_approval')}}</small>
                        @php
                            $mgrStatus = $checklog->manager_approval_status;
                            $mgrColor = $mgrStatus === 1 ? 'success' : ($mgrStatus === 2 ? 'danger' : 'warning');
                            $mgrText = $mgrStatus === 1 ? __('common.approve') : ($mgrStatus === 2 ? __('common.reject') : __('common.pending'));
                        @endphp
                        <span class="badge bg-{{ $mgrColor }} w-100 py-2">{{ $mgrText }}</span>
                    </div>
                </div>

                <!-- Created Date -->
                <div class="text-center mb-2">
                    <small class="text-muted">
                        <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        {{$checklog->created_at->format('M d, Y')}}
                    </small>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="card-footer bg-white border-top d-flex gap-2 justify-content-between">
                @canany(['ticking-update'])
                <button 
                    wire:click="initData({{ $checklog->id }})"
                    class="btn btn-sm btn-primary flex-grow-1 d-flex align-items-center justify-content-center gap-1"
                    data-bs-toggle="modal"
                    data-bs-target="#EditChecklogModal"
                    title="{{ __('common.edit') }}">
                    <svg class="icon icon-xs" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    {{__('common.edit')}}
                </button>
                @endcanany

                @canany(['ticking-delete'])
                <button 
                    wire:click="initData({{ $checklog->id }})"
                    class="btn btn-sm btn-danger flex-grow-1 d-flex align-items-center justify-content-center gap-1"
                    data-bs-toggle="modal"
                    data-bs-target="#DeleteModal"
                    title="{{ __('common.delete') }}">
                    <svg class="icon icon-xs" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    {{__('common.delete')}}
                </button>
                @endcanany
            </div>
        </div>
    </div>
    @endif
    @empty
    <div class="col-12">
        <div class='border-prim rounded p-5 d-flex justify-content-center align-items-center flex-column text-center'>
            <img src="{{asset('/img/empty.svg')}}" alt='{{__("common.nothing_here")}}' class="mb-3" style="width: 120px; opacity: 0.6;">
            <h4 class="fs-4 fw-bold text-gray-700">{{__('common.oops_nothing_here')}} &#128540;</h4>
            <p class="text-gray-600">{{__('employees.record_checkin_message')}}</p>
        </div>
    </div>
    @endforelse
</div>

<style>
.hover-shadow {
    transition: all 0.3s ease;
}
.card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1) !important;
}
.bg-gradient-light {
    color: white;
}
.border-bottom-2 {
    border-bottom: 3px solid rgba(255, 255, 255, 0.2) !important;
}
</style>
