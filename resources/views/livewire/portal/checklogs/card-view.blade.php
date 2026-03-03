{{-- Compact Card View for Checkin Approval - 4-5 Columns Layout --}}
<div class="row g-2">
    @forelse($checklogs as $checklog)
    @if(!empty($checklog->user))
    <div class="col-12 col-sm-6 col-md-4 col-lg-2-4">
        <div class="card h-100 shadow-sm border-0 hover-shadow transition-all">
            <!-- Header: Employee Info (Compact) -->
            <div class="card-header bg-white border-bottom p-2">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2 flex-grow-1 min-w-0">
                        <div class="avatar-sm d-flex align-items-center justify-content-center fw-bold fs-6 rounded bg-primary text-white flex-shrink-0">
                            {{$checklog->user->initials}}
                        </div>
                        <div class="text-truncate">
                            <h6 class="mb-0 fw-bold text-truncate" title="{{ucwords($checklog->user_full_name)}}">{{ucwords($checklog->user_full_name)}}</h6>
                        </div>
                    </div>
                    <div class="form-check ms-2 flex-shrink-0" style="margin-right: 0;">
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

            <!-- Body: Compact Details -->
            <div class="card-body p-2">
                <!-- Hours Worked -->
                <div class="mb-2 p-2 bg-light rounded small">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted fw-bold">Hrs:</span>
                        <span class="badge bg-primary">{{$checklog->time_worked}}</span>
                    </div>
                </div>
            </div>

            <!-- Status Badges: Supervisory & Manager Approvals -->
            <div class="card-body p-2 bg-light border-top">
                @php
                    $supStatus = $checklog->supervisor_approval_status;
                    $supColor = $supStatus === 1 ? 'success' : ($supStatus === 2 ? 'danger' : 'warning');
                    $supText = $supStatus === 1 ? 'Aprv' : ($supStatus === 2 ? 'Rej' : 'Pend');
                    
                    $mgrStatus = $checklog->manager_approval_status;
                    $mgrColor = $mgrStatus === 1 ? 'success' : ($mgrStatus === 2 ? 'danger' : 'warning');
                    $mgrText = $mgrStatus === 1 ? 'Aprv' : ($mgrStatus === 2 ? 'Rej' : 'Pend');
                @endphp
                <div class="d-flex gap-1 justify-content-center align-items-center mb-2">
                    <span class="badge bg-{{ $supColor }} small">S: {{ $supText }}</span>
                    <span class="badge bg-{{ $mgrColor }} small">M: {{ $mgrText }}</span>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="card-footer bg-white border-top p-2 d-grid gap-1">
                @canany(['ticking-update'])
                <button 
                    wire:click="initData({{ $checklog->id }})"
                    class="btn btn-sm btn-primary d-flex align-items-center justify-content-center gap-1"
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
                    class="btn btn-sm btn-danger d-flex align-items-center justify-content-center gap-1"
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
.col-lg-2-4 {
    flex: 0 0 calc(20% - 0.5rem);
}
@media (max-width: 1399.98px) {
    .col-lg-2-4 {
        flex: 0 0 calc(25% - 0.5rem);
    }
}
.hover-shadow {
    transition: all 0.3s ease;
}
.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1) !important;
}
.avatar-sm {
    width: 32px;
    height: 32px;
    font-size: 0.75rem;
}
</style>
