<!-- Feature Configuration Section - Two Column Layout -->
<div class="row">
    <!-- Left Column: SFTP Configuration -->
    <div class="col-lg-7">
        <div class="mb-5 card shadow-md card-raised" style="min-height: 600px;">
            <div class="py-4 px-5 card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="card-title h5 mb-0">
                        <i class="fas fa-network-wired text-info me-2"></i>{{ __('settings.sftp_payslip_integration') }}
                    </div>
                </div>

                <x-form-items.form wire:submit="saveSftpConfiguration">
                    <!-- Enable SFTP -->
                    <div class="form-group mb-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" wire:model.live="sftp_sync_enabled" id="sftpEnabled">
                            <label class="form-check-label" for="sftpEnabled">
                                <strong>{{ __('settings.enable_sftp_sync') }}</strong>
                            </label>
                        </div>
                    </div>

                    @if ($sftp_sync_enabled)
                        <hr>

                        <!-- Push Configuration -->
                        <div class="mb-4">
                            <h6 class="text-primary mb-3">{{ __('settings.sftp_push_configuration') }}</h6>
                            <p class="text-muted small mb-3">{{ __('settings.sftp_push_configuration_help') }}</p>
                            
                            <!-- Push Path -->
                            <div class="form-group mb-3">
                                <label for="sftp_push_path">{{ __('settings.sftp_push_path') }}<span class="text-danger">*</span></label>
                                <input wire:model="sftp_push_path" id="sftp_push_path" type="text" placeholder="storage/app/sftp-push" class="form-control w-100 @error('sftp_push_path') is-invalid @enderror" required>
                                <small class="d-block text-muted mt-2">{{ __('settings.sftp_push_path_help') }}</small>
                                @error('sftp_push_path')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Push Credentials -->
                            <div class="form-group mb-3">
                                <label for="sftp_push_username">{{ __('settings.sftp_push_username') }}<span class="text-danger">*</span></label>
                                <input wire:model="sftp_push_username" id="sftp_push_username" type="text" class="form-control w-100 @error('sftp_push_username') is-invalid @enderror" readonly>
                                @error('sftp_push_username')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group mb-3">
                                <label for="sftp_push_password">{{ __('settings.sftp_push_password') }}<span class="text-danger">*</span></label>
                                <div class="d-flex gap-2 align-items-center">
                                    <div class="flex-grow-1 position-relative">
                                        <input wire:model="sftp_push_password" id="sftp_push_password" :type="show_push_password ? 'text' : 'password'" class="form-control w-100 @error('sftp_push_password') is-invalid @enderror" readonly>
                                    </div>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="$toggle('show_push_password')">
                                        <i :class="show_push_password ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                                    </button>
                                </div>
                                @error('sftp_push_password')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Generate/Regenerate Credentials -->
                            <div class="form-group mb-3">
                                <button type="button" wire:click="generateSftpPushCredentials" class="btn btn-outline-primary btn-sm" wire:loading.attr="disabled">
                                    <i class="fas fa-key me-2" wire:loading.class="spinner-border spinner-border-sm"></i>
                                    @if ($sftp_push_username && $sftp_push_password)
                                        {{ __('settings.regenerate_credentials') }}
                                    @else
                                        {{ __('settings.generate_credentials') }}
                                    @endif
                                </button>
                            </div>

                            <!-- Generated Credentials Display (One-time) -->
                            @if ($sftp_generated_username_display && $sftp_generated_password_display)
                                <div class="alert alert-warning mb-0 py-3" role="alert">
                                    <strong>{{ __('settings.sftp_credentials_generated_title') }}</strong>
                                    <p class="mb-2 small">{{ __('settings.sftp_credentials_generated_message') }}</p>
                                    <div class="mb-2">
                                        <label class="form-label small mb-0">{{ __('settings.sftp_push_username') }}</label>
                                        <div class="d-flex align-items-center gap-2">
                                            <code class="flex-grow-1 py-2 px-2 bg-white border rounded" id="push-generated-username">{{ $sftp_generated_username_display }}</code>
                                            <button type="button" class="btn btn-sm btn-outline-secondary sftp-copy-btn" data-copy-target="push-generated-username">
                                                {{ __('settings.copy') }}
                                            </button>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small mb-0">{{ __('settings.sftp_push_password') }}</label>
                                        <div class="d-flex align-items-center gap-2">
                                            <code class="flex-grow-1 py-2 px-2 bg-white border rounded font-monospace" id="push-generated-password">{{ $sftp_generated_password_display }}</code>
                                            <button type="button" class="btn btn-sm btn-outline-secondary sftp-copy-btn" data-copy-target="push-generated-password">
                                                {{ __('settings.copy') }}
                                            </button>
                                        </div>
                                    </div>
                                    <button type="button" wire:click="saveSftpPushCredentials" class="btn btn-sm btn-primary me-2">
                                        <i class="fas fa-save me-1"></i>{{ __('settings.save_credentials') }}
                                    </button>
                                    <button type="button" wire:click="$set('sftp_generated_username_display', null); $set('sftp_generated_password_display', null)" class="btn btn-sm btn-outline-secondary">
                                        {{ __('common.cancel') }}
                                    </button>
                                </div>
                            @endif

                            <!-- Test Configuration Button -->
                            <div class="form-group mt-4">
                                <button type="button" wire:click="testSftpPushConfiguration" class="btn btn-outline-info btn-sm" wire:loading.attr="disabled">
                                    <i class="fas fa-plug me-2" wire:loading.class="spinner-border spinner-border-sm"></i>
                                    {{ __('settings.test_push_configuration') }}
                                </button>
                                @if ($sftp_connection_status)
                                    <span class="badge bg-success ms-2 px-3 py-2">
                                        <i class="fas fa-check-circle me-1"></i>{{ __('settings.connected') }}
                                    </span>
                                @else
                                    <span class="badge bg-secondary ms-2 px-3 py-2">
                                        <i class="fas fa-times-circle me-1"></i>{{ __('settings.disconnected') }}
                                    </span>
                                @endif
                                @if ($test_sftp_message)
                                    <div class="mt-2 small text-muted">{{ $test_sftp_message }}</div>
                                @endif
                            </div>
                        </div>

                        <hr>

                        <!-- Sync Settings -->
                        <div class="mb-4">
                            <h6 class="text-primary mb-3">{{ __('settings.sftp_sync_frequency') }}</h6>
                            
                            <div class="form-group row mb-3">
                                <div class="col-md-6">
                                    <label for="sync_freq">{{ __('settings.sftp_sync_frequency') }}</label>
                                    <select wire:model="sftp_sync_frequency" id="sync_freq" class="form-control w-100 @error('sftp_sync_frequency') is-invalid @enderror">
                                        <option value="hourly">{{ __('settings.sftp_hourly') }}</option>
                                        <option value="daily">{{ __('settings.sftp_daily') }}</option>
                                        <option value="weekly">{{ __('settings.sftp_weekly') }}</option>
                                    </select>
                                    <small class="d-block text-muted mt-2">{{ __('settings.sftp_sync_frequency_help') }}</small>
                                    @error('sftp_sync_frequency')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr>

                        <!-- Matching Strategies -->
                        <div class="mb-4">
                            <h6 class="text-primary mb-3">{{ __('settings.sftp_matching_strategies') }}</h6>
                            <small class="d-block text-muted mb-3">{{ __('settings.sftp_matching_strategies_help') }}</small>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" wire:model="sftp_matching_strategies" value="employee_id" id="strategy_emp_id">
                                        <label class="form-check-label" for="strategy_emp_id">
                                            {{ __('settings.sftp_strategy_employee_id') }}
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" wire:model="sftp_matching_strategies" value="department_code" id="strategy_dept_code">
                                        <label class="form-check-label" for="strategy_dept_code">
                                            {{ __('settings.sftp_strategy_department_code') }}
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" wire:model="sftp_matching_strategies" value="company_code" id="strategy_company_code">
                                        <label class="form-check-label" for="strategy_company_code">
                                            {{ __('settings.sftp_strategy_company_code') }}
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" wire:model="sftp_matching_strategies" value="folder_structure" id="strategy_folder">
                                        <label class="form-check-label" for="strategy_folder">
                                            {{ __('settings.sftp_strategy_folder_structure') }}
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" wire:model="sftp_matching_strategies" value="fuzzy" id="strategy_timestamps">
                                        <label class="form-check-label" for="strategy_timestamps">
                                            {{ __('settings.sftp_strategy_timestamps') }}
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="mt-4 mb-0 form-group d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                            <i class="fas fa-save me-2" wire:loading.class="spinner-border spinner-border-sm"></i>{{ __('common.save') }}
                        </button>
                    </div>
                </x-form-items.form>
            </div>
        </div>
        <script>
            if (typeof window.sftpCopyHandlerBound === 'undefined') {
                window.sftpCopyHandlerBound = true;
                document.addEventListener('click', function(e) {
                    var btn = e.target.closest('.sftp-copy-btn');
                    if (!btn) return;
                    var id = btn.getAttribute('data-copy-target');
                    var el = document.getElementById(id);
                    if (el) {
                        navigator.clipboard.writeText(el.textContent);
                        btn.textContent = btn.getAttribute('data-copied-label');
                        setTimeout(function() { btn.textContent = btn.getAttribute('data-copy-label'); }, 2000);
                    }
                });
            }
        </script>
    </div>

    <!-- Right Column: Inactivity Deactivation -->
    <div class="col-lg-5">
        <div class="mb-5 card shadow-md card-raised" style="min-height: 600px;">
            <div class="py-4 px-5 card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="card-title h5 mb-0">
                        <i class="fas fa-user-slash text-warning me-2"></i>{{ __('settings.inactivity_deactivation') }}
                    </div>
                </div>

                <x-form-items.form wire:submit="saveFeatureConfiguration">
                    <div class="form-group mb-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" wire:model.live="inactivity_deactivation_enabled" id="inactivityEnabled">
                            <label class="form-check-label" for="inactivityEnabled">
                                <strong>{{ __('settings.enable_inactivity_deactivation') }}</strong>
                            </label>
                        </div>
                        <small class="d-block text-muted mt-2">{{ __('settings.inactivity_deactivation_description') }}</small>
                    </div>

                    @if ($inactivity_deactivation_enabled)
                        <hr>
                        <h6 class="text-primary mb-3">{{ __('settings.deactivation_settings') }}</h6>
                        <div class="form-group mb-3">
                            <label for="inactivity_months">{{ __('settings.inactivity_months_threshold') }}<span class="text-danger">*</span></label>
                            <input wire:model="inactivity_months_threshold" id="inactivity_months" type="number" min="1" max="24" class="form-control w-100 @error('inactivity_months_threshold') is-invalid @enderror">
                            <small class="d-block text-muted mt-2">{{ __('settings.inactivity_months_threshold_help') }}</small>
                            @error('inactivity_months_threshold')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group mb-3">
                            <label for="deactivation_time">{{ __('settings.deactivation_check_time') }}<span class="text-danger">*</span></label>
                            <input wire:model="deactivation_check_time" id="deactivation_time" type="time" class="form-control w-100 @error('deactivation_check_time') is-invalid @enderror">
                            <small class="d-block text-muted mt-2">{{ __('settings.deactivation_check_time_help') }}</small>
                            @error('deactivation_check_time')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    @endif

                    <div class="mt-4 mb-0 form-group d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                            <i class="fas fa-save me-2" wire:loading.class="spinner-border spinner-border-sm"></i>{{ __('common.save') }}
                        </button>
                    </div>
                </x-form-items.form>
            </div>
        </div>
    </div>
</div>
