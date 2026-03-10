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

                            <!-- HTTP Upload Command -->
                            <div class="mt-4">
                                <label class="form-label fw-semibold mb-2"><i class="fas fa-terminal me-1"></i>Upload Command</label>
                                <small class="d-block text-muted mb-2">
                                    Use this command to push a payslip PDF from any system (payroll software, script, or cron job).
                                </small>

                                <div class="bg-dark rounded p-3 position-relative">
                                    <code class="text-success d-block" id="curl-upload-cmd" style="font-size: 0.82rem; word-break: break-all; white-space: pre-wrap;">curl -F "file=@/path/to/payslip.pdf" \
     -u {{ $sftp_push_username ?: '<username>' }}:{{ $sftp_push_password ?: '<password>' }} \
     {{ url('/api/sftp-push/upload') }}</code>
                                    <button type="button"
                                        class="btn btn-sm btn-outline-light position-absolute top-0 end-0 m-2 sftp-copy-btn"
                                        data-copy-target="curl-upload-cmd"
                                        data-copy-label="Copy"
                                        data-copied-label="Copied!">
                                        Copy
                                    </button>
                                </div>
                                @if (!$sftp_push_username || !$sftp_push_password)
                                    <small class="text-warning d-block mt-2"><i class="fas fa-exclamation-triangle me-1"></i>Generate credentials above to fill in your username and password.</small>
                                @else
                                    <small class="text-muted d-block mt-2">Replace <code>/path/to/payslip.pdf</code> with the actual file path. The file must be a PDF.</small>
                                @endif
                            </div>
                        </div>

                        <hr>

                        <!-- Company Matching -->
                        <div class="mb-4">
                            <h6 class="text-primary mb-3">
                                <i class="fas fa-search me-1"></i>Company Matching
                            </h6>
                            <small class="d-block text-muted mb-3">
                                Uploaded PDFs are matched against your company list automatically using the following three-step process. No configuration is required.
                            </small>

                            <div class="d-flex flex-column gap-2">
                                <div class="d-flex align-items-start gap-3 p-3 bg-light rounded">
                                    <span class="badge bg-success text-white" style="min-width:28px; text-align:center;">1</span>
                                    <div>
                                        <strong>Partial match</strong> <span class="badge bg-success text-white ms-1">≥ 90%</span><br>
                                        <small class="text-muted">The company name contains the text extracted from the PDF header (e.g. PDF says "PERENCO", company is "PERENCO CAMEROUN").</small>
                                    </div>
                                </div>
                                <div class="d-flex align-items-start gap-3 p-3 bg-light rounded">
                                    <span class="badge bg-info text-dark" style="min-width:28px; text-align:center;">2</span>
                                    <div>
                                        <strong>Reverse partial match</strong> <span class="badge bg-info text-dark ms-1">≥ 85%</span><br>
                                        <small class="text-muted">The PDF header contains the company name as a whole word (e.g. PDF says "CIBLE RH — PERENCO SITE", company is "PERENCO").</small>
                                    </div>
                                </div>
                                <div class="d-flex align-items-start gap-3 p-3 bg-light rounded">
                                    <span class="badge bg-warning text-dark" style="min-width:28px; text-align:center;">3</span>
                                    <div>
                                        <strong>Fuzzy match</strong> <span class="badge bg-warning text-dark ms-1">variable (≥ 50%)</span><br>
                                        <small class="text-muted">Character-level similarity scoring between the PDF text and each company name. Handles typos and abbreviated names.</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <!-- Period Extraction -->
                        <div class="mb-4">
                            <h6 class="text-primary mb-3">
                                <i class="fas fa-calendar-alt me-1"></i>Pay Period Extraction
                            </h6>
                            <small class="d-block text-muted mb-3">
                                The pay period (month &amp; year) is extracted from each PDF automatically using a 6-pass priority chain. Both 2-digit and 4-digit years are supported.
                            </small>

                            <div class="d-flex flex-column gap-2">
                                <div class="d-flex align-items-start gap-3 p-3 bg-light rounded">
                                    <span class="badge bg-secondary text-white" style="min-width:28px; text-align:center;">1</span>
                                    <div>
                                        <strong>"Période du DD/MM/YY[YY]"</strong><br>
                                        <small class="text-muted">Explicit French pay-period label. Works even when the date appears on a separate column due to PDF layout.</small>
                                    </div>
                                </div>
                                <div class="d-flex align-items-start gap-3 p-3 bg-light rounded">
                                    <span class="badge bg-secondary text-white" style="min-width:28px; text-align:center;">2</span>
                                    <div>
                                        <strong>"au DD/MM/YY[YY]"</strong><br>
                                        <small class="text-muted">End-of-period marker that appears above the label in columnar PDFs. The month/year of this end date is used.</small>
                                    </div>
                                </div>
                                <div class="d-flex align-items-start gap-3 p-3 bg-light rounded">
                                    <span class="badge bg-secondary text-white" style="min-width:28px; text-align:center;">3</span>
                                    <div>
                                        <strong>"Du DD/MM/YY au DD/MM/YY"</strong><br>
                                        <small class="text-muted">Full date range on a single line — uses the end date.</small>
                                    </div>
                                </div>
                                <div class="d-flex align-items-start gap-3 p-3 bg-light rounded">
                                    <span class="badge bg-secondary text-white" style="min-width:28px; text-align:center;">4</span>
                                    <div>
                                        <strong>French/English month name + year</strong><br>
                                        <small class="text-muted">e.g. "Décembre 2025" or "December 25" anywhere in the text.</small>
                                    </div>
                                </div>
                                <div class="d-flex align-items-start gap-3 p-3 bg-light rounded">
                                    <span class="badge bg-secondary text-white" style="min-width:28px; text-align:center;">5</span>
                                    <div>
                                        <strong>Filename patterns</strong><br>
                                        <small class="text-muted">YYYY-MM, MM-YYYY, YY-MM, or French month name in the filename (e.g. <code>payslip_2025-12.pdf</code>).</small>
                                    </div>
                                </div>
                                <div class="d-flex align-items-start gap-3 p-3 bg-light rounded">
                                    <span class="badge bg-secondary text-white" style="min-width:28px; text-align:center;">6</span>
                                    <div>
                                        <strong>Standalone end-of-month date</strong><br>
                                        <small class="text-muted">Any DD/MM/YY[YY] where the day is a typical month-end value (28, 29, 30, 31).</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <!-- Auto-Match Configuration -->
                        <div class="mb-4">
                            <h6 class="text-primary mb-3">
                                <i class="fas fa-magic me-1"></i>Auto-Match Configuration
                            </h6>
                            <small class="d-block text-muted mb-3">
                                When enabled, proposals whose confidence meets the threshold will be automatically validated and appear in the Validated tab for one-click processing.
                            </small>

                            <div class="form-group mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" wire:model.live="sftp_auto_match_enabled" id="autoMatchEnabled">
                                    <label class="form-check-label" for="autoMatchEnabled">
                                        <strong>Enable auto-match</strong>
                                    </label>
                                </div>
                            </div>

                            @if ($sftp_auto_match_enabled)
                                <!-- Confidence threshold -->
                                <div class="form-group mb-3">
                                    <label for="sftp_auto_match_threshold">
                                        Confidence threshold: <strong>{{ $sftp_auto_match_threshold }}%</strong>
                                    </label>
                                    <input type="range" class="form-range" min="50" max="100" step="1"
                                        wire:model.live="sftp_auto_match_threshold" id="sftp_auto_match_threshold">
                                    <small class="text-muted">Proposals below this confidence score will stay pending for manual review.</small>
                                </div>

                                <!-- Minimum strategy -->
                                <div class="form-group mb-3">
                                    <label for="sftp_auto_match_min_strategy">Minimum matching strategy</label>
                                    <select wire:model="sftp_auto_match_min_strategy" id="sftp_auto_match_min_strategy" class="form-control w-100">
                                        <option value="partial_match">Partial match (≥ 90% typical) — highest precision</option>
                                        <option value="reverse_partial_match">Reverse partial (≥ 85% typical) — recommended</option>
                                        <option value="fuzzy">Fuzzy — any strategy allowed</option>
                                    </select>
                                    <small class="text-muted">Strategies below the selected quality level will not trigger auto-match.</small>
                                </div>

                                <!-- Notification email -->
                                <div class="form-group mb-3">
                                    <label for="sftp_auto_match_notification_email">Notification email addresses</label>
                                    <textarea wire:model="sftp_auto_match_notification_email"
                                        id="sftp_auto_match_notification_email"
                                        rows="3"
                                        placeholder="admin@example.com, hr@example.com"
                                        class="form-control w-100"></textarea>
                                    <small class="text-muted">
                                        Separate multiple addresses with a comma.
                                        An email will be sent to all recipients when a proposal is auto-validated or when a department cannot be inferred.
                                        Each email includes a direct link to open the proposal — recipients must log in before they can review or process it.
                                    </small>
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
