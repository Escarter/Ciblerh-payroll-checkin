<!-- Feature Configuration Section -->
<div class="row">
    <!-- SFTP Payslip Integration -->
    <div class="col-12 order-2">
        <div class="mb-5 card shadow-md card-raised">
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
                                            <button type="button" class="btn btn-sm btn-outline-secondary sftp-copy-btn" data-copy-target="push-generated-username" data-copy-label="{{ __('settings.copy') }}" data-copied-label="✓">
                                                {{ __('settings.copy') }}
                                            </button>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small mb-0">{{ __('settings.sftp_push_password') }}</label>
                                        <div class="d-flex align-items-center gap-2">
                                            <code class="flex-grow-1 py-2 px-2 bg-white border rounded font-monospace" id="push-generated-password">{{ $sftp_generated_password_display }}</code>
                                            <button type="button" class="btn btn-sm btn-outline-secondary sftp-copy-btn" data-copy-target="push-generated-password" data-copy-label="{{ __('settings.copy') }}" data-copied-label="✓">
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

                            <!-- HTTP Upload Command -->
                            <div class="mt-4">
                                <label class="form-label fw-semibold mb-2"><i class="fas fa-terminal me-1"></i>{{ __('settings.sftp_upload_command_label') }}</label>
                                <small class="d-block text-muted mb-2">
                                    {{ __('settings.sftp_upload_command_help') }}
                                </small>

                                <div class="bg-dark rounded p-3 position-relative">
                                    <code class="text-success d-block" id="curl-upload-cmd" style="font-size: 0.82rem; word-break: break-all; white-space: pre-wrap;">curl -F "file=@/path/to/payslip.pdf" \
     -u {{ $sftp_push_username ?: '<username>' }}:{{ $sftp_push_password ?: '<password>' }} \
     {{ url('/api/sftp-push/upload') }}</code>
                                    <button type="button"
                                        class="btn btn-sm btn-outline-light position-absolute top-0 end-0 m-2 sftp-copy-btn"
                                        data-copy-target="curl-upload-cmd"
                                        data-copy-label="{{ __('settings.copy') }}"
                                        data-copied-label="✓">
                                        {{ __('settings.copy') }}
                                    </button>
                                </div>
                                @if (!$sftp_push_username || !$sftp_push_password)
                                    <small class="text-warning d-block mt-2"><i class="fas fa-exclamation-triangle me-1"></i>{{ __('settings.sftp_upload_no_credentials_hint') }}</small>
                                @else
                                    {!! __('settings.sftp_upload_file_hint') !!}
                                @endif
                            </div>

                            <!-- SFTP Client Access -->
                            <div class="mt-4 pt-3 border-top">
                                <label class="form-label fw-semibold mb-1">
                                    <i class="fas fa-plug me-1"></i>{{ __('settings.sftp_client_access_label') }}
                                    <span class="badge bg-secondary ms-2" style="font-size:0.7rem;">FileZilla / WinSCP / sftp</span>
                                </label>
                                <small class="d-block text-muted mb-3">
                                    {{ __('settings.sftp_client_access_help') }}
                                </small>

                                @if ($sftp_os_username && $sftp_os_password)
                                    <!-- Connection details grid -->
                                    <div class="row g-2 mb-3">
                                        <div class="col-sm-5">
                                            <label class="form-label small mb-1">{{ __('settings.sftp_client_host_label') }}</label>
                                            <div class="d-flex gap-2">
                                                <code class="flex-grow-1 px-2 py-1 bg-light border rounded d-block" id="sftp-host">{{ $sftp_server_host }}</code>
                                                <button type="button" class="btn btn-sm btn-outline-secondary sftp-copy-btn" data-copy-target="sftp-host" data-copy-label="{{ __('settings.copy') }}" data-copied-label="✓">{{ __('settings.copy') }}</button>
                                            </div>
                                        </div>
                                        <div class="col-sm-2">
                                            <label class="form-label small mb-1">{{ __('settings.sftp_client_port_label') }}</label>
                                            <code class="px-2 py-1 bg-light border rounded d-block text-center" id="sftp-port">{{ $sftp_server_port }}</code>
                                        </div>
                                        <div class="col-sm-5">
                                            <label class="form-label small mb-1">{{ __('settings.sftp_client_remote_path_label') }}</label>
                                            <code class="px-2 py-1 bg-light border rounded d-block text-truncate" id="sftp-path" title="/incoming">/incoming</code>
                                        </div>
                                        <div class="col-sm-5">
                                            <label class="form-label small mb-1">{{ __('settings.sftp_client_username_label') }}</label>
                                            <div class="d-flex gap-2">
                                                <code class="flex-grow-1 px-2 py-1 bg-light border rounded d-block" id="sftp-os-user">{{ $sftp_os_username }}</code>
                                                <button type="button" class="btn btn-sm btn-outline-secondary sftp-copy-btn" data-copy-target="sftp-os-user" data-copy-label="{{ __('settings.copy') }}" data-copied-label="✓">{{ __('settings.copy') }}</button>
                                            </div>
                                        </div>
                                        <div class="col-sm-5">
                                            <label class="form-label small mb-1">{{ __('settings.sftp_client_password_label') }}</label>
                                            <div class="d-flex gap-2">
                                                <code class="flex-grow-1 px-2 py-1 bg-light border rounded d-block" id="sftp-os-pass">{{ $sftp_os_password }}</code>
                                                <button type="button" class="btn btn-sm btn-outline-secondary sftp-copy-btn" data-copy-target="sftp-os-pass" data-copy-label="{{ __('settings.copy') }}" data-copied-label="✓">{{ __('settings.copy') }}</button>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                @if ($sftp_os_generated_display)
                                    <!-- One-time server setup script -->
                                    <div class="alert alert-warning py-3 mb-3">
                                        <strong><i class="fas fa-exclamation-triangle me-1"></i>{{ __('settings.sftp_server_script_run_title') }}</strong>
                                        <div class="bg-dark rounded p-3 mt-2 position-relative">
                                            <code class="text-success d-block" id="sftp-server-script" style="font-size:0.8rem; white-space:pre;">{{ $sftp_os_generated_display['script'] }}</code>
                                            <button type="button"
                                                class="btn btn-sm btn-outline-light position-absolute top-0 end-0 m-2 sftp-copy-btn"
                                                data-copy-target="sftp-server-script"
                                                data-copy-label="{{ __('settings.sftp_server_script_copy_btn') }}"
                                                data-copied-label="✓">
                                                {{ __('settings.sftp_server_script_copy_btn') }}
                                            </button>
                                        </div>
                                        <small class="d-block mt-2 text-muted">
                                            {{ __('settings.sftp_server_script_desc_intro') }}
                                            <ol class="mt-1 mb-0 ps-3">
                                                <li>{!! __('settings.sftp_server_script_step_1', ['username' => $sftp_os_generated_display['username']]) !!}</li>
                                                <li>{!! __('settings.sftp_server_script_step_2') !!}</li>
                                                <li>{!! __('settings.sftp_server_script_step_3') !!}</li>
                                                <li>{!! __('settings.sftp_server_script_step_4') !!}</li>
                                                <li>{!! __('settings.sftp_server_script_step_5', ['username' => $sftp_os_generated_display['username']]) !!}</li>
                                                <li>{!! __('settings.sftp_server_script_step_6') !!}</li>
                                            </ol>
                                            <span class="d-block mt-1">{!! __('settings.sftp_server_script_after', ['host' => e($sftp_os_generated_display['host']), 'port' => e($sftp_os_generated_display['port'])]) !!}</span>
                                        </small>
                                    </div>
                                @endif

                                <!-- Server host/port fields (editable) -->
                                <div class="row g-2 mb-3">
                                    <div class="col-sm-7">
                                        <label class="form-label small mb-1" for="sftp_server_host">{{ __('settings.sftp_server_host_label') }}</label>
                                        <input wire:model="sftp_server_host" id="sftp_server_host" type="text" class="form-control form-control-sm" placeholder="portail.example.com">
                                    </div>
                                    <div class="col-sm-3">
                                        <label class="form-label small mb-1" for="sftp_server_port">{{ __('settings.sftp_server_port_label') }}</label>
                                        <input wire:model="sftp_server_port" id="sftp_server_port" type="number" min="1" max="65535" class="form-control form-control-sm" placeholder="22">
                                    </div>
                                </div>

                                <div class="d-flex flex-wrap gap-2 align-items-center">
                                    @if ($sftp_os_username)
                                        {{-- Existing credentials: show script without changing them --}}
                                        <button type="button" wire:click="generateSftpOsCredentials" class="btn btn-outline-secondary btn-sm" wire:loading.attr="disabled">
                                            <i class="fas fa-file-code me-1"></i>
                                            {{ __('settings.sftp_show_server_script') }}
                                        </button>
                                        {{-- Explicit rotate: generates new username + password --}}
                                        <button type="button" wire:click="regenerateSftpOsCredentials" class="btn btn-outline-danger btn-sm" wire:loading.attr="disabled"
                                            data-confirm="{{ __('settings.sftp_rotate_confirm') }}"
                                            onclick="return confirm(this.dataset.confirm)">
                                            <i class="fas fa-sync-alt me-1"></i>
                                            {{ __('settings.sftp_rotate_credentials') }}
                                        </button>
                                    @else
                                        <button type="button" wire:click="generateSftpOsCredentials" class="btn btn-outline-primary btn-sm" wire:loading.attr="disabled">
                                            <i class="fas fa-user-plus me-1"></i>
                                            {{ __('settings.sftp_generate_os_user') }}
                                        </button>
                                    @endif
                                </div>
                                <small class="d-block text-muted mt-2">
                                    @if ($sftp_os_username)
                                        {!! __('settings.sftp_credentials_exist_help') !!}
                                    @else
                                        {{ __('settings.sftp_generate_os_user_help') }}
                                    @endif
                                    {{ __('settings.sftp_auto_pickup_note') }}
                                </small>
                            </div>
                        </div>

                        <hr>

                        <!-- Push Scan Frequency -->
                        <div class="mb-4">
                            <h6 class="text-primary mb-3">
                                <i class="fas fa-clock me-1"></i>{{ __('settings.sftp_push_scan_frequency_title') }}
                            </h6>
                            <small class="d-block text-muted mb-3">
                                {{ __('settings.sftp_push_scan_frequency_help') }}
                            </small>

                            <div class="row g-3">
                                <!-- Frequency type -->
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">{{ __('settings.sftp_push_scan_frequency_label') }}</label>
                                    <select class="form-select" wire:model.live="sftp_push_scan_frequency">
                                        <optgroup label="{{ __('settings.sftp_push_scan_group_minutes') }}">
                                            <option value="everyMinute">{{ __('settings.sftp_push_scan_every_minute') }}</option>
                                            <option value="everyFiveMinutes">{{ __('settings.sftp_push_scan_every_5_minutes') }}</option>
                                            <option value="everyTenMinutes">{{ __('settings.sftp_push_scan_every_10_minutes') }}</option>
                                            <option value="everyFifteenMinutes">{{ __('settings.sftp_push_scan_every_15_minutes') }}</option>
                                            <option value="everyThirtyMinutes">{{ __('settings.sftp_push_scan_every_30_minutes') }}</option>
                                        </optgroup>
                                        <optgroup label="{{ __('settings.sftp_push_scan_group_hours') }}">
                                            <option value="hourly">{{ __('settings.sftp_push_scan_hourly') }}</option>
                                            <option value="everyTwoHours">{{ __('settings.sftp_push_scan_every_2_hours') }}</option>
                                            <option value="everyThreeHours">{{ __('settings.sftp_push_scan_every_3_hours') }}</option>
                                            <option value="everyFourHours">{{ __('settings.sftp_push_scan_every_4_hours') }}</option>
                                            <option value="everySixHours">{{ __('settings.sftp_push_scan_every_6_hours') }}</option>
                                            <option value="everyTwelveHours">{{ __('settings.sftp_push_scan_every_12_hours') }}</option>
                                        </optgroup>
                                        <optgroup label="{{ __('settings.sftp_push_scan_group_schedule') }}">
                                            <option value="daily">{{ __('settings.sftp_push_scan_daily') }}</option>
                                            <option value="custom_days">{{ __('settings.sftp_push_scan_custom_days') }}</option>
                                        </optgroup>
                                    </select>
                                </div>

                                <!-- Time picker (shown for daily and custom_days) -->
                                @if(in_array($sftp_push_scan_frequency, ['daily', 'custom_days']))
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">{{ __('settings.sftp_push_scan_time_label') }}</label>
                                    <input type="time" class="form-control" wire:model="sftp_push_scan_time">
                                </div>
                                @endif
                            </div>

                            <!-- Day checkboxes (shown for custom_days) -->
                            @if($sftp_push_scan_frequency === 'custom_days')
                            <div class="mt-3">
                                <label class="form-label fw-semibold">{{ __('settings.sftp_push_scan_days_label') }}</label>
                                <div class="d-flex flex-wrap gap-3">
                                    @foreach(['0' => __('settings.day_sunday'), '1' => __('settings.day_monday'), '2' => __('settings.day_tuesday'), '3' => __('settings.day_wednesday'), '4' => __('settings.day_thursday'), '5' => __('settings.day_friday'), '6' => __('settings.day_saturday')] as $dayNum => $dayName)
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="scan_day_{{ $dayNum }}"
                                            value="{{ $dayNum }}"
                                            wire:model="sftp_push_scan_days">
                                        <label class="form-check-label" for="scan_day_{{ $dayNum }}">{{ $dayName }}</label>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @endif
                        </div>

                        <hr>

                        <!-- Company Matching -->
                        <div class="mb-4">
                            <h6 class="text-primary mb-3">
                                <i class="fas fa-search me-1"></i>{{ __('settings.sftp_company_matching') }}
                            </h6>
                            <small class="d-block text-muted mb-3">
                                {{ __('settings.sftp_company_matching_help') }}
                            </small>

                            <div class="d-flex flex-column gap-2">
                                <div class="d-flex align-items-start gap-3 p-3 bg-light rounded">
                                    <span class="badge bg-success text-white" style="min-width:28px; text-align:center;">1</span>
                                    <div>
                                        <strong>{{ __('settings.sftp_match_partial') }}</strong> <span class="badge bg-success text-white ms-1">≥ 90%</span><br>
                                        <small class="text-muted">{{ __('settings.sftp_match_partial_desc') }}</small>
                                    </div>
                                </div>
                                <div class="d-flex align-items-start gap-3 p-3 bg-light rounded">
                                    <span class="badge bg-info text-dark" style="min-width:28px; text-align:center;">2</span>
                                    <div>
                                        <strong>{{ __('settings.sftp_match_reverse_partial') }}</strong> <span class="badge bg-info text-dark ms-1">≥ 85%</span><br>
                                        <small class="text-muted">{{ __('settings.sftp_match_reverse_partial_desc') }}</small>
                                    </div>
                                </div>
                                <div class="d-flex align-items-start gap-3 p-3 bg-light rounded">
                                    <span class="badge bg-warning text-dark" style="min-width:28px; text-align:center;">3</span>
                                    <div>
                                        <strong>{{ __('settings.sftp_match_fuzzy') }}</strong> <span class="badge bg-warning text-dark ms-1">variable (≥ 50%)</span><br>
                                        <small class="text-muted">{{ __('settings.sftp_match_fuzzy_desc') }}</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <!-- Period Extraction -->
                        <div class="mb-4">
                            <h6 class="text-primary mb-3">
                                <i class="fas fa-calendar-alt me-1"></i>{{ __('settings.sftp_period_extraction') }}
                            </h6>
                            <small class="d-block text-muted mb-3">
                                {{ __('settings.sftp_period_extraction_help') }}
                            </small>

                            <div class="d-flex flex-column gap-2">
                                <div class="d-flex align-items-start gap-3 p-3 bg-light rounded">
                                    <span class="badge bg-secondary text-white" style="min-width:28px; text-align:center;">1</span>
                                    <div>
                                        <strong>"Période du DD/MM/YY[YY]"</strong><br>
                                        <small class="text-muted">{{ __('settings.sftp_period_pass_1_desc') }}</small>
                                    </div>
                                </div>
                                <div class="d-flex align-items-start gap-3 p-3 bg-light rounded">
                                    <span class="badge bg-secondary text-white" style="min-width:28px; text-align:center;">2</span>
                                    <div>
                                        <strong>"au DD/MM/YY[YY]"</strong><br>
                                        <small class="text-muted">{{ __('settings.sftp_period_pass_2_desc') }}</small>
                                    </div>
                                </div>
                                <div class="d-flex align-items-start gap-3 p-3 bg-light rounded">
                                    <span class="badge bg-secondary text-white" style="min-width:28px; text-align:center;">3</span>
                                    <div>
                                        <strong>"Du DD/MM/YY au DD/MM/YY"</strong><br>
                                        <small class="text-muted">{{ __('settings.sftp_period_pass_3_desc') }}</small>
                                    </div>
                                </div>
                                <div class="d-flex align-items-start gap-3 p-3 bg-light rounded">
                                    <span class="badge bg-secondary text-white" style="min-width:28px; text-align:center;">4</span>
                                    <div>
                                        <strong>{{ __('settings.sftp_period_pass_4_label') }}</strong><br>
                                        <small class="text-muted">{{ __('settings.sftp_period_pass_4_desc') }}</small>
                                    </div>
                                </div>
                                <div class="d-flex align-items-start gap-3 p-3 bg-light rounded">
                                    <span class="badge bg-secondary text-white" style="min-width:28px; text-align:center;">5</span>
                                    <div>
                                        <strong>{{ __('settings.sftp_period_pass_5_label') }}</strong><br>
                                        {!! __('settings.sftp_period_pass_5_desc') !!}
                                    </div>
                                </div>
                                <div class="d-flex align-items-start gap-3 p-3 bg-light rounded">
                                    <span class="badge bg-secondary text-white" style="min-width:28px; text-align:center;">6</span>
                                    <div>
                                        <strong>{{ __('settings.sftp_period_pass_6_label') }}</strong><br>
                                        <small class="text-muted">{{ __('settings.sftp_period_pass_6_desc') }}</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <!-- Auto-Match Configuration -->
                        <div class="mb-4">
                            <h6 class="text-primary mb-3">
                                <i class="fas fa-magic me-1"></i>{{ __('settings.sftp_auto_match_configuration') }}
                            </h6>
                            <small class="d-block text-muted mb-3">
                                {{ __('settings.sftp_auto_match_configuration_help') }}
                            </small>

                            <div class="form-group mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" wire:model.live="sftp_auto_match_enabled" id="autoMatchEnabled">
                                    <label class="form-check-label" for="autoMatchEnabled">
                                        <strong>{{ __('settings.sftp_auto_match_enable') }}</strong>
                                    </label>
                                </div>
                            </div>

                            @if ($sftp_auto_match_enabled)
                                <!-- Confidence threshold -->
                                <div class="form-group mb-3">
                                    <label for="sftp_auto_match_threshold">
                                        {{ __('settings.sftp_auto_match_threshold_label', ['value' => $sftp_auto_match_threshold]) }}
                                    </label>
                                    <input type="range" class="form-range" min="50" max="100" step="1"
                                        wire:model.live="sftp_auto_match_threshold" id="sftp_auto_match_threshold">
                                    <small class="text-muted">{{ __('settings.sftp_auto_match_threshold_help') }}</small>
                                </div>

                                <!-- Minimum strategy -->
                                <div class="form-group mb-3">
                                    <label for="sftp_auto_match_min_strategy">{{ __('settings.sftp_auto_match_min_strategy_label') }}</label>
                                    <select wire:model="sftp_auto_match_min_strategy" id="sftp_auto_match_min_strategy" class="form-control w-100">
                                        <option value="partial_match">{{ __('settings.sftp_auto_match_strategy_partial') }}</option>
                                        <option value="reverse_partial_match">{{ __('settings.sftp_auto_match_strategy_reverse') }}</option>
                                        <option value="fuzzy">{{ __('settings.sftp_auto_match_strategy_fuzzy') }}</option>
                                    </select>
                                    <small class="text-muted">{{ __('settings.sftp_auto_match_strategy_help') }}</small>
                                </div>

                                <!-- Notification email -->
                                <div class="form-group mb-3">
                                    <label for="sftp_auto_match_notification_email">{{ __('settings.sftp_auto_match_email_label') }}</label>
                                    <textarea wire:model="sftp_auto_match_notification_email"
                                        id="sftp_auto_match_notification_email"
                                        rows="3"
                                        placeholder="admin@example.com, hr@example.com"
                                        class="form-control w-100"></textarea>
                                    <small class="text-muted">{{ __('settings.sftp_auto_match_email_help') }}</small>
                                </div>
                            @endif
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

    <!-- Inactivity Deactivation -->
    <div class="col-12 order-1">
        <div class="mb-5 card shadow-md card-raised">
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

    <script>
        (function () {
            function sftpCopyHandler(e) {
                var btn = e.target.closest('.sftp-copy-btn');
                if (!btn) return;
                var id = btn.getAttribute('data-copy-target');
                var el = document.getElementById(id);
                if (!el) return;
                navigator.clipboard.writeText(el.textContent.trim()).then(function () {
                    var orig = btn.getAttribute('data-copy-label') || btn.textContent.trim();
                    btn.textContent = btn.getAttribute('data-copied-label') || '✓';
                    setTimeout(function () { btn.textContent = orig; }, 2000);
                });
            }

            // Attach once on initial load
            if (!window._sftpCopyBound) {
                window._sftpCopyBound = true;
                document.addEventListener('click', sftpCopyHandler);
            }

            // Re-attach after Livewire morphs the DOM (Livewire 3)
            document.addEventListener('livewire:navigated', function () {
                window._sftpCopyBound = false;
            });
        })();
    </script>
</div>
