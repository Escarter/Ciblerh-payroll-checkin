<div>
    @if($showModal)
    <div wire:ignore.self class="modal fade show" style="display: block;" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <svg class="icon icon-sm me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"></path>
                        </svg>
                        {{ __('User Roles') }} - {{ $user->first_name ?? '' }} {{ $user->last_name ?? '' }}
                    </h5>
                    <button type="button" class="btn-close" wire:click="closeModal"></button>
                </div>
                <div class="modal-body">
                    @if (session()->has('message'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('message') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <div class="row">
                        <!-- Current Roles -->
                        <div class="col-md-7">
                            <h6 class="fw-bold mb-3">
                                <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                {{ __('Assigned Roles') }}
                            </h6>
                            
                            @if(count($userRoles) > 0)
                                <div class="list-group">
                                    @foreach($userRoles as $role)
                                        <div class="list-group-item d-flex justify-content-between align-items-center">
                                            <div>
                                                <span class="badge bg-primary me-2">{{ strtoupper(substr($role['name'], 0, 2)) }}</span>
                                                <span class="fw-medium">{{ ucfirst($role['name']) }}</span>
                                                @if($role['name'] === 'employee')
                                                    <small class="text-muted">(Default)</small>
                                                @endif
                                            </div>
                                            @if($role['name'] !== 'employee' || count($userRoles) > 1)
                                                <button wire:click="removeRole({{ $role['id'] }})" 
                                                        class="btn btn-sm btn-outline-danger"
                                                        onclick="return confirm('Are you sure you want to remove this role?')">
                                                    <svg class="icon icon-xs" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                    </svg>
                                                </button>
                                            @else
                                                <small class="text-muted">Cannot remove</small>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center text-muted py-4">
                                    <svg class="icon icon-lg mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                    </svg>
                                    <p>{{ __('No roles assigned') }}</p>
                                </div>
                            @endif

                            @error('removeRole')
                                <div class="alert alert-danger mt-2">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Assign Roles -->
                        <div class="col-md-5">
                            <h6 class="fw-bold mb-3">
                                <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                {{ __('Manage Roles') }}
                            </h6>
                            
                            <form wire:submit.prevent="assignRoles">
                                <div class="mb-3">
                                    <x-choices-multi-select
                                        id="user_roles_selection"
                                        wireModel="selectedRoles"
                                        :options="$availableRoles->pluck('name', 'name')->map(fn($name) => ucfirst($name))->toArray()"
                                        :selected="$selectedRoles"
                                        label="{{__('Select Roles (Max 2)')}}"
                                        help="{{__('Maximum 2 roles allowed. Employee role is automatically included.')}}"
                                        class="form-select" />
                                    @error('selectedRoles')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <button type="submit" class="btn btn-primary btn-sm w-100">
                                    <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                    </svg>
                                    {{ __('Update Roles') }}
                                </button>
                            </form>

                            <div class="mt-4 p-3 bg-light rounded">
                                <small class="text-muted">
                                    <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <strong>{{ __('Note:') }}</strong> 
                                    <ul class="mb-0 mt-1">
                                        <li>{{ __('Maximum 2 roles per user allowed') }}</li>
                                        <li>{{ __('Employee role is automatically included') }}</li>
                                        <li>{{ __('Use Ctrl/Cmd + Click for multiple selection') }}</li>
                                    </ul>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="closeModal">{{ __('Close') }}</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show"></div>
    @endif
</div>
