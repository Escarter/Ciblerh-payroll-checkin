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

                        <!-- Multi-user SFTP Configuration -->
                        <div class="mb-4">
                            <h6 class="text-primary mb-2">
                                <i class="fas fa-users me-1"></i>{{ __('settings.sftp_users_title') }}
                                <span class="badge bg-secondary ms-2" style="font-size:0.7rem;">FileZilla / WinSCP / curl</span>
                            </h6>
                            <p class="text-muted small mb-3">{{ __('settings.sftp_users_help') }}</p>

                            <!-- Server host/port (used when generating OS scripts) -->
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

                            @if (count($sftpUsers) > 0)
                                <!-- User table -->
                                <div class="table-responsive mb-3">
                                    <table class="table table-sm table-bordered align-middle mb-0" style="font-size:0.85rem;">
                                        <thead class="table-light">
                                            <tr>
                                                <th>{{ __('settings.sftp_user_col_username') }}</th>
                                                <th>{{ __('settings.sftp_user_col_password') }}</th>
                                                <th>{{ __('settings.sftp_user_col_home_dir') }}</th>
                                                <th style="width:180px;">{{ __('common.actions') }}</th>
                                            </tr>
                                        </thead>
                                        {{-- One <tbody x-data> per user so Alpine state is scoped per row --}}
                                        @foreach ($sftpUsers as $sftpUser)
                                        <tbody x-data="{ showScript: false, showPass: false }">
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center gap-1">
                                                        <code class="flex-grow-1" id="sftp-user-name-{{ $sftpUser['id'] }}">{{ $sftpUser['username'] }}</code>
                                                        <button type="button" class="btn btn-xs btn-outline-secondary sftp-copy-btn py-0 px-1"
                                                            data-copy-target="sftp-user-name-{{ $sftpUser['id'] }}"
                                                            data-copy-label="{{ __('settings.copy') }}"
                                                            data-copied-label="✓"
                                                            title="{{ __('settings.copy') }}">
                                                            <i class="fas fa-copy" style="font-size:0.7rem;"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center gap-1">
                                                        {{-- Both spans always rendered; Alpine toggles visibility client-side --}}
                                                        <code class="flex-grow-1 text-muted" x-show="!showPass">••••••••</code>
                                                        <code class="flex-grow-1" x-show="showPass" style="display:none;"
                                                            id="sftp-user-pass-{{ $sftpUser['id'] }}">{{ $sftpUser['password'] }}</code>
                                                        <button x-show="showPass" style="display:none;"
                                                            type="button" class="btn btn-xs btn-outline-secondary sftp-copy-btn py-0 px-1"
                                                            data-copy-target="sftp-user-pass-{{ $sftpUser['id'] }}"
                                                            data-copy-label="{{ __('settings.copy') }}"
                                                            data-copied-label="✓"
                                                            title="{{ __('settings.copy') }}">
                                                            <i class="fas fa-copy" style="font-size:0.7rem;"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-1"
                                                            @click="showPass = !showPass"
                                                            title="{{ __('settings.sftp_toggle_password') }}">
                                                            <i class="fas" :class="showPass ? 'fa-eye-slash' : 'fa-eye'" style="font-size:0.7rem;"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                                <td>
                                                    <small class="text-muted font-monospace">{{ $sftpUser['home_directory'] }}</small>
                                                </td>
                                                <td>
                                                    <div class="d-flex gap-1 flex-wrap">
                                                        {{-- Single button toggled by Alpine — no server round-trip --}}
                                                        <button type="button"
                                                            class="btn btn-xs py-0 px-2"
                                                            :class="showScript ? 'btn-outline-secondary' : 'btn-outline-info'"
                                                            @click="showScript = !showScript">
                                                            <span x-show="!showScript"><i class="fas fa-file-code me-1"></i>{{ __('settings.sftp_show_server_script') }}</span>
                                                            <span x-show="showScript" style="display:none;"><i class="fas fa-eye-slash me-1"></i>{{ __('settings.sftp_hide_server_script') }}</span>
                                                        </button>
                                                        <button type="button"
                                                            class="btn btn-xs btn-outline-danger py-0 px-2"
                                                            wire:click="confirmRemoveSftpUser({{ $sftpUser['id'] }})"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#DeleteModal"
                                                            title="{{ __('common.remove') }}">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>

                                            {{-- OS Script panel — always rendered, toggled by Alpine x-show --}}
                                            <tr class="table-warning" x-show="showScript" style="display:none;">
                                                <td colspan="4" class="py-2 px-3">
                                                    <strong><i class="fas fa-terminal me-1"></i>{{ __('settings.sftp_server_script_run_title') }}</strong>
                                                    <div class="bg-dark rounded p-2 mt-1 position-relative">
                                                        <code class="text-success d-block"
                                                            id="sftp-script-{{ $sftpUser['id'] }}"
                                                            style="font-size:0.75rem; white-space:pre;">{{ $sftpUser['script'] }}</code>
                                                        <button type="button"
                                                            class="btn btn-sm btn-outline-light position-absolute top-0 end-0 m-1 sftp-copy-btn"
                                                            data-copy-target="sftp-script-{{ $sftpUser['id'] }}"
                                                            data-copy-label="{{ __('settings.sftp_server_script_copy_btn') }}"
                                                            data-copied-label="✓">
                                                            {{ __('settings.sftp_server_script_copy_btn') }}
                                                        </button>
                                                    </div>
                                                    <small class="d-block mt-1 text-muted">
                                                        {!! __('settings.sftp_server_script_after', ['host' => e($sftpUser['host']), 'port' => e($sftpUser['port'])]) !!}
                                                    </small>
                                                </td>
                                            </tr>
                                        </tbody>
                                        @endforeach
                                    </table>
                                </div>
                            @else
                                <div class="alert alert-light border mb-3">
                                    <i class="fas fa-info-circle me-1 text-muted"></i>
                                    <span class="text-muted small">{{ __('settings.sftp_users_empty') }}</span>
                                </div>
                            @endif

                            <!-- Add User button (max 4) -->
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <button type="button"
                                    wire:click="addSftpUser"
                                    class="btn btn-sm btn-outline-primary"
                                    wire:loading.attr="disabled"
                                    @if(count(array_filter($sftpUsers, fn($u) => $u['is_active'])) >= 4) disabled @endif>
                                    <i class="fas fa-user-plus me-1"></i>{{ __('settings.sftp_add_user') }}
                                </button>
                                @if(count(array_filter($sftpUsers, fn($u) => $u['is_active'])) >= 4)
                                    <small class="text-warning"><i class="fas fa-exclamation-triangle me-1"></i>{{ __('settings.sftp_users_max_reached') }}</small>
                                @else
                                    <small class="text-muted">{{ __('settings.sftp_users_max_note') }}</small>
                                @endif
                            </div>

                            <!-- HTTP Upload Command (curl) -->
                            <div class="mt-2 pt-3 border-top">
                                <label class="form-label fw-semibold mb-1"><i class="fas fa-terminal me-1"></i>{{ __('settings.sftp_upload_command_label') }}</label>
                                <small class="d-block text-muted mb-2">{{ __('settings.sftp_upload_command_help') }}</small>
                                <div class="bg-dark rounded p-3 position-relative">
                                    <code class="text-success d-block" id="curl-upload-cmd" style="font-size: 0.82rem; word-break: break-all; white-space: pre-wrap;">curl -F "file=@/path/to/payslip.pdf" \
     -u &lt;username&gt;:&lt;password&gt; \
     {{ url('/api/sftp-push/upload') }}</code>
                                    <button type="button"
                                        class="btn btn-sm btn-outline-light position-absolute top-0 end-0 m-2 sftp-copy-btn"
                                        data-copy-target="curl-upload-cmd"
                                        data-copy-label="{{ __('settings.copy') }}"
                                        data-copied-label="✓">
                                        {{ __('settings.copy') }}
                                    </button>
                                </div>
                                {!! __('settings.sftp_upload_file_hint') !!}
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

                        <!-- Push Archive Configuration -->
                        <div class="mb-4">
                            <h6 class="text-primary mb-3">
                                <i class="fas fa-box-archive me-1"></i>{{ __('settings.sftp_push_archive_title') }}
                            </h6>
                            <small class="d-block text-muted mb-3">
                                {{ __('settings.sftp_push_archive_help') }}
                            </small>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">{{ __('settings.sftp_push_archive_frequency_label') }}</label>
                                    <select class="form-select" wire:model.live="sftp_push_archive_frequency">
                                        <option value="">{{ __('settings.sftp_push_archive_frequency_follow_scan') }}</option>
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

                                @if(in_array($sftp_push_archive_frequency, ['daily', 'custom_days']))
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">{{ __('settings.sftp_push_archive_time_label') }}</label>
                                    <input type="time" class="form-control" wire:model="sftp_push_archive_time">
                                </div>
                                @endif

                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">{{ __('settings.sftp_push_archive_min_age_label') }}</label>
                                    <input type="number" min="0" class="form-control" wire:model="sftp_push_archive_min_age_minutes">
                                    <small class="text-muted">{{ __('settings.sftp_push_archive_min_age_help') }}</small>
                                </div>
                            </div>

                            @if($sftp_push_archive_frequency === 'custom_days')
                            <div class="mb-3">
                                <label class="form-label fw-semibold">{{ __('settings.sftp_push_archive_days_label') }}</label>
                                <div class="d-flex flex-wrap gap-3">
                                    @foreach(['0' => __('settings.day_sunday'), '1' => __('settings.day_monday'), '2' => __('settings.day_tuesday'), '3' => __('settings.day_wednesday'), '4' => __('settings.day_thursday'), '5' => __('settings.day_friday'), '6' => __('settings.day_saturday')] as $dayNum => $dayName)
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="archive_day_{{ $dayNum }}"
                                            value="{{ $dayNum }}"
                                            wire:model="sftp_push_archive_days">
                                        <label class="form-check-label" for="archive_day_{{ $dayNum }}">{{ $dayName }}</label>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @endif

                            <div class="row g-2">
                                <div class="col-md-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" wire:model.live="sftp_push_archive_move_processed" id="archiveMoveProcessed">
                                        <label class="form-check-label" for="archiveMoveProcessed">{{ __('settings.sftp_push_archive_move_processed_label') }}</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" wire:model.live="sftp_push_archive_require_successful_process" id="archiveRequireProcessSuccess">
                                        <label class="form-check-label" for="archiveRequireProcessSuccess">{{ __('settings.sftp_push_archive_require_success_label') }}</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" wire:model.live="sftp_push_archive_move_rejected" id="archiveMoveRejected">
                                        <label class="form-check-label" for="archiveMoveRejected">{{ __('settings.sftp_push_archive_move_rejected_label') }}</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" wire:model.live="sftp_push_archive_move_failed" id="archiveMoveFailed">
                                        <label class="form-check-label" for="archiveMoveFailed">{{ __('settings.sftp_push_archive_move_failed_label') }}</label>
                                    </div>
                                </div>
                            </div>

                            <div class="alert alert-light border mt-3 mb-0 py-2">
                                <small class="text-muted d-block">
                                    <strong>{{ __('settings.sftp_push_archive_effective_preview_label') }}</strong>
                                    {{ $sftp_push_archive_frequency ? __('settings.sftp_push_archive_effective_preview_frequency_custom', ['frequency' => $sftp_push_archive_frequency]) : __('settings.sftp_push_archive_effective_preview_frequency_scan') }}
                                    · {{ __('settings.sftp_push_archive_effective_preview_min_age', ['minutes' => (int) $sftp_push_archive_min_age_minutes]) }}
                                    · {{ __('settings.sftp_push_archive_effective_preview_rules', [
                                        'processed' => $sftp_push_archive_move_processed ? __('common.yes') : __('common.no'),
                                        'rejected' => $sftp_push_archive_move_rejected ? __('common.yes') : __('common.no'),
                                        'failed' => $sftp_push_archive_move_failed ? __('common.yes') : __('common.no'),
                                        'require_success' => $sftp_push_archive_require_successful_process ? __('common.yes') : __('common.no'),
                                    ]) }}
                                </small>
                            </div>
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

                                {{-- Proposal Created Notification --}}
                                <div class="form-check form-switch mb-3">
                                    <input type="checkbox" wire:model="sftp_match_created_notification_enabled"
                                        id="sftp_match_created_notification_enabled" class="form-check-input">
                                    <label for="sftp_match_created_notification_enabled" class="form-check-label">
                                        {{ __('settings.sftp_match_created_notification_enabled_label') }}
                                    </label>
                                </div>

                                @if ($sftp_match_created_notification_enabled)
                                <div class="form-group mb-3">
                                    <label for="sftp_match_created_notification_email">{{ __('settings.sftp_match_created_email_label') }}</label>
                                    <textarea wire:model="sftp_match_created_notification_email"
                                        id="sftp_match_created_notification_email"
                                        rows="3"
                                        placeholder="admin@example.com, hr@example.com"
                                        class="form-control w-100"></textarea>
                                    <small class="text-muted">{{ __('settings.sftp_match_created_email_help') }}</small>
                                </div>
                                @endif
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
