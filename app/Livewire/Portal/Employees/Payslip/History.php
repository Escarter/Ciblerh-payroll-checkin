<?php

namespace App\Livewire\Portal\Employees\Payslip;

use App\Models\User;
use App\Models\Payslip;
use App\Models\Setting;
use App\Services\Nexah;
use Livewire\Component;
use App\Mail\SendPayslip;
use App\Services\TwilioSMS;
use App\Services\AwsSnsSMS;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use App\Livewire\Traits\WithDataTable;
use Illuminate\Support\Facades\Storage;

class History extends Component
{
    use WithDataTable;

    public $employee;

    public ?Payslip $payslip;
    public ?Payslip $selectedPayslip = null;
    
    // Soft delete properties
    public $activeTab = 'active';
    public $selectedPayslips = [];
    public $selectAll = false;

    public function mount($employee_uuid)  
    {
        $this->employee = User::whereUuid($employee_uuid)->first();

    }

    public function initData($payslip_id)
    {
        if(!empty($payslip_id))
        {
            $this->payslip = Payslip::withTrashed()->findOrFail($payslip_id);
        }
    }

    public function downloadPayslip($payslip_id)
    {
        $payslip = Payslip::findOrFail($payslip_id);

        // Check if encryption was successful
        if ($payslip->encryption_status != 1) {
            $this->showToast(__('payslips.encryption_not_successful'), 'danger');
            return;
        }

        // Check if the file exists
        if (!Storage::disk('modified')->exists($payslip->file)) {
            $this->showToast(__('payslips.payslip_file_not_found'), 'danger');
            return;
        }
        
        try {
            return response()->download(
                Storage::disk('modified')->path($payslip->file), 
                $payslip->matricule. "_" . $payslip->year.'_'.$payslip->month.'.pdf', 
                ['Content-Type'=> 'application/pdf']
            );
        } catch (\Exception $e) {
            $this->showToast(__('payslips.unable_to_download_payslip'), 'danger');
        }
    }
    public function resendEmail()
    {
        if (!empty($this->payslip)) {
            // Check if encryption was successful
            if ($this->payslip->encryption_status != 1) {
                $this->closeModalAndFlashMessage(__('payslips.encryption_not_successful'), 'resendEmailModal');
                return;
            }

            $employee = User::findOrFail($this->payslip->employee->id);


                if (Storage::disk('modified')->exists($this->payslip->file)) {

                    $destination_file = $this->payslip->file;


                    if (!empty($employee->email)) {

                        try {
                            setSavedSmtpCredentials();

                            Mail::to(cleanString($employee->email))->send(new SendPayslip($employee, $destination_file, $this->payslip->month));

                            // Email accepted by mail server - mark as sent (not yet delivered)
                            // The MailSentListener will update to 'delivered' when Laravel confirms the send
                            // Webhooks will update to 'delivered' when email provider confirms delivery
                            $this->payslip->update([
                                'email_sent_status' => Payslip::STATUS_SUCCESSFUL, // Keep as successful for backward compatibility
                                'email_delivery_status' => Payslip::DELIVERY_STATUS_SENT, // Mark as sent
                                'email_sent_at' => now(),
                                'email_retry_count' => 0, // Reset retry count on success
                                'last_email_retry_at' => null,
                                'failure_reason' => null, // Clear failure reason
                            ]);

                            // sendSmsAndUpdateRecord($employee, $this->payslip->month, $this->payslip);

                            Log::info('mail-sent');
                            auditLog(
                                auth()->user(),
                                'send_email',
                                'web',
                                'send_email_to_employee',
                                null,
                                [],
                                [],
                                [
                                    'translation_key' => 'send_email_to_employee',
                                    'translation_params' => [
                                        'user' => '<a href="/admin/users?user_id=' . auth()->user()->id . '">' . auth()->user()->name . '</a>',
                                        'employee' => '<a href="/admin/groups/' . $employee->group_id . '/employees?employee_id=' . $employee->id . '">' . $employee->name . '</a>'
                                    ],
                                ]
                            );

                            $this->closeModalAndFlashMessage(__('payslips.email_resent_successfully'), 'resendEmailModal');

                        } catch (\Swift_TransportException $e) {

                            Log::info('------> err swift:--  ' . $e->getMessage()); // for log, remove if you not want it
                            Log::info('' . PHP_EOL . '');
                            $this->payslip->update([
                                'email_sent_status' => Payslip::STATUS_FAILED,
                                'sms_sent_status' => Payslip::STATUS_FAILED,
                                'failure_reason' => $e->getMessage()
                            ]);
                        $this->closeModalAndFlashMessage(__('payslips.failed_to_resent_email'), 'resendEmailModal');
                        } catch (\Swift_RfcComplianceException $e) {
                            Log::info('------> err Swift_Rfc:' . $e->getMessage());
                            Log::info('' . PHP_EOL . '');

                            $this->payslip->update([
                                'email_sent_status' => Payslip::STATUS_FAILED,
                                'sms_sent_status' => Payslip::STATUS_FAILED,
                                'failure_reason' => $e->getMessage()
                            ]);
                        $this->closeModalAndFlashMessage(__('payslips.failed_to_resent_email'), 'resendEmailModal');
                        } catch (Exception $e) {
                            Log::info('------> err' . $e->getMessage());
                            Log::info('' . PHP_EOL . '');

                            $this->payslip->update([
                                'email_sent_status' => Payslip::STATUS_FAILED,
                                'sms_sent_status' => Payslip::STATUS_FAILED,
                                'failure_reason' => $e->getMessage()
                            ]);
                        $this->closeModalAndFlashMessage(__('payslips.failed_to_resent_email'), 'resendEmailModal');
                        }
                    } else {
                        $this->payslip->update([
                            'email_sent_status' => Payslip::STATUS_FAILED,
                            'sms_sent_status' => Payslip::STATUS_FAILED,
                            'failure_reason' => __('payslips.no_valid_email_address')
                        ]);
                    $this->closeModalAndFlashMessage(__('payslips.failed_to_resent_email'), 'resendEmailModal');
                    }
                }
            
        }
    }

    public function resendSMS()
    {
        if(!empty($this->payslip)){
            // Check if encryption was successful
            if ($this->payslip->encryption_status != 1) {
                $this->closeModalAndFlashMessage(__('payslips.encryption_not_successful'), 'resendSMSModal');
                return;
            }

            $setting = Setting::first();

            $sms_client = match ($setting->sms_provider) {
                'twilio' => new TwilioSMS($setting),
                'nexah' =>  new Nexah($setting),
                'aws_sns' => new AwsSnsSMS($setting),
                default => new Nexah($setting)
            };

            $balance = $sms_client->getBalance();
            if($balance['credit'] !== 0){
                $employee = User::findOrFail($this->payslip->employee->id);

                if (auth()->user()->hasRole('user') && $employee->group->user_id !== auth()->user()->id) {
                    return abort(401);
                }

                sendSmsAndUpdateRecord($employee, $this->payslip->month, $this->payslip, $balance);

                auditLog(
                    auth()->user(),
                    'send_sms',
                    'web',
                    'send_sms_to_employee',
                    null,
                    [],
                    [],
                    [
                        'translation_key' => 'send_sms_to_employee',
                        'translation_params' => [
                            'user' => '<a href="/admin/users?user_id=' . auth()->user()->id . '">' . auth()->user()->name . '</a>',
                            'employee' => '<a href="/admin/groups/' . $employee->group_id . '/employees?employee_id=' . $employee->id . '">' . $employee->name . '</a>'
                        ],
                    ]
                );
                $this->closeModalAndFlashMessage(__('payslips.sms_sent_successfully', ['user' => $employee->name]), 'resendSMSModal');
    
            }else{

                
                $this->closeModalAndFlashMessage(__('payslips.insufficient_sms_balance'), 'resendSMSModal');
            }
         
        }

    }

    public function delete($payslipId = null)
    {
        if (!Gate::allows('payslip-delete')) {
            return abort(401);
        }

        try {
            $payslip = $payslipId ? Payslip::findOrFail($payslipId) : $this->payslip;

            if (!empty($payslip)) {
                $payslip->delete(); // Soft delete
                $this->closeModalAndFlashMessage(__('payslips.payslip_successfully_moved_to_trash'), 'DeleteModal');
            } else {
                $this->showToast(__('payslips.payslip_not_found'), 'danger');
            }
        } catch (\Exception $e) {
            $this->showToast(__('payslips.error_deleting_payslip') . $e->getMessage(), 'danger');
        }
        
        $this->reset(['payslip']);
    }

    public function restore($payslipId)
    {
        if (!Gate::allows('payslip-restore')) {
            return abort(401);
        }

        $payslip = Payslip::withTrashed()->findOrFail($payslipId);
        $payslip->restore();

        $this->closeModalAndFlashMessage(__('payslips.payslip_successfully_restored'), 'RestoreModal');
    }

    public function forceDelete($payslipId = null)
    {
        if (!Gate::allows('payslip-delete')) {
            return abort(401);
        }

        try {
            $payslip = $payslipId ? Payslip::withTrashed()->findOrFail($payslipId) : $this->payslip;

            if (!empty($payslip)) {
                $payslip->forceDelete();
                $this->closeModalAndFlashMessage(__('payslips.payslip_permanently_deleted'), 'ForceDeleteModal');
            } else {
                $this->showToast(__('payslips.payslip_not_found'), 'danger');
            }
        } catch (\Exception $e) {
            $this->showToast(__('payslips.error_deleting_payslip') . $e->getMessage(), 'danger');
        }
        
        $this->reset(['payslip']);
    }

    public function bulkDelete()
    {
        if (!Gate::allows('payslip-bulkdelete')) {
            return abort(401);
        }

        $targetIds = $this->selectedPayslips ?? [];
        $payslips = collect();
        $affectedRecords = [];

        if (!empty($targetIds)) {
            $payslips = Payslip::withTrashed()->whereIn('id', $targetIds)->get();
            $affectedRecords = $payslips->map(function ($payslip) {
                return [
                    'id' => $payslip->id,
                    'employee_id' => $payslip->employee_id,
                    'month' => $payslip->month,
                    'year' => $payslip->year,
                ];
            })->toArray();
        }

        if (!empty($targetIds)) {
            Payslip::whereIn('id', $targetIds)->delete(); // Soft delete
            $this->selectedPayslips = [];

            if ($payslips->count() > 0) {
                auditLog(
                    auth()->user(),
                    'payslip_bulk_deleted',
                    'web',
                    'bulk_deleted_payslips',
                    null,
                    [],
                    [],
                    [
                        'translation_key' => 'bulk_deleted_payslips',
                        'translation_params' => ['count' => $payslips->count()],
                        'bulk_operation' => true,
                        'operation_type' => 'soft_delete',
                        'affected_count' => $payslips->count(),
                        'affected_ids' => $payslips->pluck('id')->toArray(),
                        'affected_records' => $affectedRecords,
                    ]
                );
            }
        }

        $this->closeModalAndFlashMessage(__('payslips.selected_payslips_moved_to_trash'), 'BulkDeleteModal');
    }

    public function bulkRestore()
    {
        if (!Gate::allows('payslip-bulkrestore')) {
            return abort(401);
        }

        $targetIds = $this->selectedPayslips ?? [];
        $payslips = collect();
        $affectedRecords = [];

        if (!empty($targetIds)) {
            $payslips = Payslip::withTrashed()->whereIn('id', $targetIds)->get();
            $affectedRecords = $payslips->map(function ($payslip) {
                return [
                    'id' => $payslip->id,
                    'employee_id' => $payslip->employee_id,
                    'month' => $payslip->month,
                    'year' => $payslip->year,
                ];
            })->toArray();
        }

        if (!empty($targetIds)) {
            Payslip::withTrashed()->whereIn('id', $targetIds)->restore();
            $this->selectedPayslips = [];

            if ($payslips->count() > 0) {
                auditLog(
                    auth()->user(),
                    'payslip_bulk_restored',
                    'web',
                    'bulk_restored_payslips',
                    null,
                    [],
                    [],
                    [
                        'translation_key' => 'bulk_restored_payslips',
                        'translation_params' => ['count' => $payslips->count()],
                        'bulk_operation' => true,
                        'operation_type' => 'bulk_restore',
                        'affected_count' => $payslips->count(),
                        'affected_ids' => $payslips->pluck('id')->toArray(),
                        'affected_records' => $affectedRecords,
                    ]
                );
            }
        }

        $this->closeModalAndFlashMessage(__('payslips.selected_payslips_restored'), 'BulkRestoreModal');
    }

    public function bulkForceDelete()
    {
        if (!Gate::allows('payslip-delete')) {
            return abort(401);
        }

        $targetIds = $this->selectedPayslips ?? [];
        $payslips = collect();
        $affectedRecords = [];

        if (!empty($targetIds)) {
            $payslips = Payslip::withTrashed()->whereIn('id', $targetIds)->get();
            $affectedRecords = $payslips->map(function ($payslip) {
                return [
                    'id' => $payslip->id,
                    'employee_id' => $payslip->employee_id,
                    'month' => $payslip->month,
                    'year' => $payslip->year,
                ];
            })->toArray();
        }

        if (!empty($targetIds)) {
            Payslip::withTrashed()->whereIn('id', $targetIds)->forceDelete();
            $this->selectedPayslips = [];

            if ($payslips->count() > 0) {
                auditLog(
                    auth()->user(),
                    'payslip_bulk_force_deleted',
                    'web',
                    'bulk_force_deleted_payslips',
                    null,
                    [],
                    [],
                    [
                        'translation_key' => 'bulk_force_deleted_payslips',
                        'translation_params' => ['count' => $payslips->count()],
                        'bulk_operation' => true,
                        'operation_type' => 'bulk_force_delete',
                        'affected_count' => $payslips->count(),
                        'affected_ids' => $payslips->pluck('id')->toArray(),
                        'affected_records' => $affectedRecords,
                    ]
                );
            }
        }

        $this->closeModalAndFlashMessage(__('payslips.selected_payslips_permanently_deleted'), 'BulkForceDeleteModal');
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
        $this->selectedPayslips = [];
        $this->selectAll = false;
    }

    public function showPayslipDetails($payslip_id)
    {
        $this->selectedPayslip = Payslip::with('sendProcess.department')->findOrFail($payslip_id);
        $this->dispatch('open-modal', 'payslipDetailsModal');
    }

    public function refreshTaskData()
    {
        // Refresh the selected payslip data
        if ($this->selectedPayslip) {
            $this->selectedPayslip->refresh();
        }
    }

    public function getTranslatedFailureReason($failureReason)
    {
        if (empty($failureReason)) {
            return '';
        }

        // Translate the "Email/SMS skipped: Encryption failed." prefix
        $encryptionSkippedPrefix = __('payslips.encryption_failed_email_sms_skipped');
        
        // Check if failure reason starts with the encryption skipped prefix (in any language)
        // We need to check both English and French versions
        $englishPrefix = 'Email/SMS skipped: Encryption failed. ';
        $frenchPrefix = 'Email/SMS ignoré: Échec du cryptage. ';
        
        $remainingReason = $failureReason;
        $hasPrefix = false;
        
        if (strpos($failureReason, $englishPrefix) === 0) {
            $remainingReason = substr($failureReason, strlen($englishPrefix));
            $hasPrefix = true;
        } elseif (strpos($failureReason, $frenchPrefix) === 0) {
            $remainingReason = substr($failureReason, strlen($frenchPrefix));
            $hasPrefix = true;
        } elseif (strpos($failureReason, $encryptionSkippedPrefix) === 0) {
            $remainingReason = substr($failureReason, strlen($encryptionSkippedPrefix));
            $hasPrefix = true;
        }
        
        // Translate the remaining reason if it matches known patterns
        $translatedReason = $remainingReason;
        
        // Helper function to normalize month name to English for translation
        $normalizeMonthName = function($monthName) {
            $monthName = trim($monthName);
            // French to English month mapping
            $frenchToEnglish = [
                'janvier' => 'January',
                'février' => 'February',
                'mars' => 'March',
                'avril' => 'April',
                'mai' => 'May',
                'juin' => 'June',
                'juillet' => 'July',
                'août' => 'August',
                'septembre' => 'September',
                'octobre' => 'October',
                'novembre' => 'November',
                'décembre' => 'December',
            ];
            
            $lowerMonth = mb_strtolower($monthName);
            if (isset($frenchToEnglish[$lowerMonth])) {
                return $frenchToEnglish[$lowerMonth];
            }
            // If it's already in English or not found, return as-is
            return $monthName;
        };
        
        // Pattern: "Matricule :matricule not found in any PDF file for month :month"
        // English pattern
        if (preg_match('/Matricule\s+([A-Z0-9]+)\s+not found in any PDF file for month\s+(.+)/i', $remainingReason, $matches)) {
            $matricule = $matches[1];
            $monthName = trim($matches[2]);
            // Normalize month name to English, then translate to current locale
            $normalizedMonth = $normalizeMonthName($monthName);
            $translatedMonth = translateMonthName($normalizedMonth);
            $translatedReason = __('payslips.matricule_not_found_in_pdf', [
                'matricule' => $matricule,
                'month' => $translatedMonth
            ]);
        }
        // French pattern: "Matricule :matricule introuvable dans tout fichier PDF pour le mois :month"
        elseif (preg_match('/Matricule\s+([A-Z0-9]+)\s+introuvable dans tout fichier PDF pour le mois\s+(.+)/i', $remainingReason, $matches)) {
            $matricule = $matches[1];
            $monthName = trim($matches[2]);
            // Normalize month name to English, then translate to current locale
            $normalizedMonth = $normalizeMonthName($monthName);
            $translatedMonth = translateMonthName($normalizedMonth);
            $translatedReason = __('payslips.matricule_not_found_in_pdf', [
                'matricule' => $matricule,
                'month' => $translatedMonth
            ]);
        }
        // Also handle "User matricule is empty" pattern
        elseif (preg_match('/User matricule is empty/i', $remainingReason) || 
                preg_match('/Le matricule de l\'utilisateur est vide/i', $remainingReason)) {
            $translatedReason = __('payslips.user_matricule_empty');
        }
        // Handle SMS provider error messages
        elseif (preg_match('/SMS provider credentials are not configured/i', $remainingReason) ||
                preg_match('/Les identifiants du fournisseur SMS ne sont pas configurés/i', $remainingReason)) {
            $translatedReason = __('payslips.sms_provider_credentials_not_configured');
        }
        elseif (preg_match('/SMS provider not configured/i', $remainingReason) ||
                preg_match('/Fournisseur SMS non configuré/i', $remainingReason)) {
            $translatedReason = __('payslips.sms_provider_not_configured');
        }
        elseif (preg_match('/SMS provider initialization failed/i', $remainingReason) ||
                preg_match('/Échec d\'initialisation du fournisseur SMS/i', $remainingReason)) {
            $translatedReason = __('payslips.sms_provider_initialization_failed');
        }
        elseif (preg_match('/SMS provider balance check failed for (.+): (.+)/i', $remainingReason, $matches) ||
                preg_match('/Échec de vérification du solde SMS pour (.+): (.+)/i', $remainingReason, $matches)) {
            $translatedReason = __('payslips.sms_provider_balance_check_failed', [
                'provider' => trim($matches[1]),
                'error' => trim($matches[2])
            ]);
        }
        elseif (preg_match('/SMS provider unhealthy during balance check/i', $remainingReason) ||
                preg_match('/Fournisseur SMS défaillant lors de la vérification du solde/i', $remainingReason)) {
            $translatedReason = __('payslips.sms_provider_unhealthy_during_balance_check');
        }
        elseif (preg_match('/SMS provider is unhealthy/i', $remainingReason) ||
                preg_match('/Le fournisseur SMS est défaillant/i', $remainingReason)) {
            $translatedReason = __('payslips.sms_provider_unhealthy');
        }
        elseif (preg_match('/SMS sending exception/i', $remainingReason) ||
                preg_match('/Exception d\'envoi SMS/i', $remainingReason)) {
            // Extract the error message after the colon if present
            if (preg_match('/SMS sending exception:\s*(.+)/i', $remainingReason, $matches) ||
                preg_match('/Exception d\'envoi SMS:\s*(.+)/i', $remainingReason, $matches)) {
                $translatedReason = __('payslips.sms_sending_exception') . ': ' . trim($matches[1]);
            } else {
                $translatedReason = __('payslips.sms_sending_exception');
            }
        }
        elseif (preg_match('/SMS sending failed with unexpected error/i', $remainingReason) ||
                preg_match('/Échec d\'envoi SMS avec erreur inattendue/i', $remainingReason)) {
            // Extract the error message after the colon if present
            if (preg_match('/SMS sending failed with unexpected error:\s*(.+)/i', $remainingReason, $matches) ||
                preg_match('/Échec d\'envoi SMS avec erreur inattendue:\s*(.+)/i', $remainingReason, $matches)) {
                $translatedReason = __('payslips.sms_unexpected_error') . ': ' . trim($matches[1]);
            } else {
                $translatedReason = __('payslips.sms_unexpected_error');
            }
        }
        elseif (preg_match('/No valid phone number for user/i', $remainingReason) ||
                preg_match('/Aucun numéro de téléphone valide pour l\'utilisateur/i', $remainingReason)) {
            $translatedReason = __('payslips.no_valid_phone_number_for_user');
        }
        elseif (preg_match('/Failed sending SMS/i', $remainingReason) ||
                preg_match('/Échec d\'envoi de SMS/i', $remainingReason)) {
            $translatedReason = __('payslips.failed_sending_sms');
        }
        elseif (preg_match('/Insufficient SMS Balance/i', $remainingReason) ||
                preg_match('/Solde SMS insuffisant/i', $remainingReason)) {
            $translatedReason = __('payslips.insufficient_sms_balance');
        }
        elseif (preg_match('/SMS provider configuration error/i', $remainingReason) ||
                preg_match('/Erreur de configuration du fournisseur SMS/i', $remainingReason)) {
            // Check if there's a specific error message after
            if (preg_match('/SMS provider configuration error:\s*(.+)/i', $remainingReason, $matches) ||
                preg_match('/Erreur de configuration du fournisseur SMS:\s*(.+)/i', $remainingReason, $matches)) {
                $errorDetail = trim($matches[1]);
                // Check if it's the null value error
                if (preg_match('/Null value in provider configuration/i', $errorDetail) ||
                    preg_match('/Valeur nulle dans la configuration du fournisseur/i', $errorDetail)) {
                    $translatedReason = __('payslips.sms_provider_configuration_error') . ': ' . __('payslips.null_value_in_provider_config');
                } else {
                    $translatedReason = __('payslips.sms_provider_configuration_error') . ': ' . $errorDetail;
                }
            } else {
                $translatedReason = __('payslips.sms_provider_configuration_error');
            }
        }
        
        // Return the translated prefix + translated reason if prefix exists, otherwise just the translated reason
        return $hasPrefix ? $encryptionSkippedPrefix . $translatedReason : $translatedReason;
    }

    public function getPayslipOverallStatus($payslip)
    {
        // Determine overall status based on encryption, email, and SMS status
        if ($payslip->encryption_status == Payslip::STATUS_FAILED ||
            $payslip->email_sent_status == Payslip::STATUS_FAILED ||
            $payslip->sms_sent_status == Payslip::STATUS_FAILED) {
            return 'failed';
        }

        if ($payslip->encryption_status == Payslip::STATUS_SUCCESSFUL &&
            $payslip->email_sent_status == Payslip::STATUS_SUCCESSFUL &&
            ($payslip->sms_sent_status == Payslip::STATUS_SUCCESSFUL || $payslip->sms_sent_status == Payslip::STATUS_DISABLED)) {
            return 'success';
        }

        return 'processing';
    }

    public function toggleSelectAll()
    {
        if ($this->selectAll) {
            $this->selectedPayslips = $this->getPayslips()->pluck('id')->toArray();
        } else {
            $this->selectedPayslips = [];
        }
    }

    public function selectAllVisible()
    {
        $this->selectedPayslips = $this->getPayslips()->pluck('id')->toArray();
    }

    public function selectAllPayslips()
    {
        $query = Payslip::where('employee_id', $this->employee->id);

        // Add soft delete filtering based on active tab
        if ($this->activeTab === 'deleted') {
            $query->withTrashed()->whereNotNull('deleted_at');
        } else {
            $query->whereNull('deleted_at');
        }

        $this->selectedPayslips = $query->pluck('id')->toArray();
    }

    public function togglePayslipSelection($payslipId)
    {
        if (in_array($payslipId, $this->selectedPayslips)) {
            $this->selectedPayslips = array_diff($this->selectedPayslips, [$payslipId]);
        } else {
            $this->selectedPayslips[] = $payslipId;
        }
        
        $this->selectAll = count($this->selectedPayslips) === $this->getPayslips()->count();
    }

    private function getPayslips()
    {
        $query = Payslip::search($this->query)->where('employee_id', $this->employee->id);

        // Add soft delete filtering based on active tab
        if ($this->activeTab === 'deleted') {
            $query->withTrashed()->whereNotNull('deleted_at');
        } else {
            $query->whereNull('deleted_at');
        }

        return $query->orderBy($this->orderBy, $this->orderAsc)->paginate($this->perPage);
    }

    public function render()
    {
        if (!Gate::allows('payslip-read')) {
            return abort(401);
        }

        // if (auth()->user()->hasRole('employee') && $this->employee->department->author_id !== auth()->user()->id) {
        //     return abort(401);
        // }
        
        $payslips = $this->getPayslips();

        // Get counts for active payslips (non-deleted)
        $active_payslips = Payslip::search($this->query)->where('employee_id', $this->employee->id)->whereNull('deleted_at')->count();
        $deleted_payslips = Payslip::search($this->query)->where('employee_id', $this->employee->id)->withTrashed()->whereNotNull('deleted_at')->count();

        return view('livewire.portal.employees.payslip.history', [
            'payslips' => $payslips,
            'payslips_count' => $active_payslips, // Legacy for backward compatibility
            'active_payslips' => $active_payslips,
            'deleted_payslips' => $deleted_payslips,
        ])->layout('components.layouts.dashboard');
    }

    public function closePayslipDetailsModal()
    {
        $this->selectedPayslip = null;
        $this->dispatch('close-payslip-details-modal');
    }

    public function resendPayslip($payslipId)
    {
        $payslip = Payslip::findOrFail($payslipId);

        // Check if payslip can be resent (failed or pending status)
        if ($payslip->encryption_status == Payslip::STATUS_SUCCESSFUL &&
            ($payslip->email_sent_status == Payslip::STATUS_FAILED ||
             $payslip->sms_sent_status == Payslip::STATUS_FAILED ||
             $payslip->email_sent_status == Payslip::STATUS_PENDING ||
             $payslip->sms_sent_status == Payslip::STATUS_PENDING)) {

            // Use the existing retry job
            \App\Jobs\Single\ResendFailedPayslipJob::dispatch($payslip);

            session()->flash('message', __('payslips.payslip_queued_for_resend'));
            $this->closePayslipDetailsModal();
        }
    }
}
