<?php 

use App\Models\User;
use App\Models\Payslip;
use App\Models\Setting;
use App\Services\Nexah;
use App\Models\AuditLog;
use App\Services\TwilioSMS;
use App\Services\AwsSnsSMS;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

function initials($string)
{
    $string_array = explode(" ",$string);
    if(count($string_array) >= 2){
        return strtoupper(Str::substr($string_array[0], 0, 1)) . "" . strtoupper(Str::substr($string_array[1], 0, 1));
    }else{
        return strtoupper(Str::substr($string_array[0], 0, 1)) ;
    }
}

if (!function_exists('auditLog')) {
    /**
     * Enhanced audit log function
     * 
     * @param User $user The user performing the action
     * @param string $action_type The action type (e.g., 'company_created', 'user_updated')
     * @param string $channel The channel (e.g., 'web', 'api')
     * @param string $action_performed Description of the action (can be translation key like 'bulk_approved_absences' or translated string)
     * @param mixed $model Optional: The model instance being affected
     * @param array $oldValues Optional: Old values for updates
     * @param array $newValues Optional: New values for creates/updates
     * @param array $metadata Optional: Additional metadata (can include 'translation_key' and 'translation_params' for proper translation)
     */
    function auditLog(
        ?User $user, 
        string $action_type, 
        string $channel, 
        string $action_performed,
        $model = null,
        array $oldValues = [],
        array $newValues = [],
        array $metadata = []
    ) {
        // If no user provided, try to get authenticated user or use system user
        if (!$user) {
            $user = auth()->user();
            if (!$user) {
                // Log error and return early if no user available
                Log::warning('Audit log attempted without user', [
                    'action_type' => $action_type,
                    'channel' => $channel,
                    'action_performed' => $action_performed,
                ]);
                return;
            }
        }
        
        $request = request();
        
        // Extract model information if provided
        $modelType = null;
        $modelId = null;
        $modelName = null;
        
        if ($model) {
            $modelType = get_class($model);
            $modelId = $model->id ?? $model->uuid ?? null;
            
            // Try to get a human-readable name
            if (method_exists($model, 'getNameAttribute') || isset($model->name)) {
                $modelName = $model->name ?? null;
            } elseif (method_exists($model, '__toString')) {
                $modelName = (string) $model;
            } elseif (isset($model->title)) {
                $modelName = $model->title;
            } elseif (isset($model->first_name) && isset($model->last_name)) {
                $modelName = $model->first_name . ' ' . $model->last_name;
            }
        }
        
        // Calculate changes if both old and new values are provided
        $changes = [];
        if (!empty($oldValues) && !empty($newValues)) {
            foreach ($newValues as $key => $newValue) {
                $oldValue = $oldValues[$key] ?? null;
                if ($oldValue !== $newValue) {
                    $changes[$key] = [
                        'old' => $oldValue,
                        'new' => $newValue,
                    ];
                }
            }
        } elseif ($model && method_exists($model, 'getDirty')) {
            // Auto-detect changes from model if it's an update
            $dirty = $model->getDirty();
            if (!empty($dirty)) {
                $original = $model->getOriginal();
                foreach ($dirty as $key => $newValue) {
                    $oldValue = $original[$key] ?? null;
                    $changes[$key] = [
                        'old' => $oldValue,
                        'new' => $newValue,
                    ];
                }
                $oldValues = array_intersect_key($original, $dirty);
                $newValues = $dirty;
            }
        }
        
        // Extract translation key and parameters from metadata if provided
        // This allows storing translation keys separately for proper translation on display
        $translationKey = $metadata['translation_key'] ?? null;
        $translationParams = $metadata['translation_params'] ?? [];
        
        // If translation key is provided, store it; otherwise store the action_performed as-is
        // Store the key in action_perform for backward compatibility and easy access
        $actionPerformToStore = $translationKey ? $translationKey : $action_performed;
        
        // Remove translation_key and translation_params from metadata to avoid duplication
        $metadataToStore = $metadata;
        if (isset($metadataToStore['translation_key'])) {
            unset($metadataToStore['translation_key']);
        }
        if (isset($metadataToStore['translation_params'])) {
            unset($metadataToStore['translation_params']);
        }
        
        // Store translation params in metadata if provided
        if ($translationKey && !empty($translationParams)) {
            $metadataToStore['translation_params'] = $translationParams;
        }
        
        try {
            AuditLog::create([
                'user_id' => $user->id,
                'user' => $user->name,
                'action_type' => $action_type,
                'channel' => $channel,
                'action_perform' => $actionPerformToStore,
                'company_id' => $user->company_id,
                'department_id' => $user->department_id,
                'author_id' => $user->author_id ?? null,
                'model_type' => $modelType,
                'model_id' => $modelId ? (string) $modelId : null,
                'model_name' => $modelName,
                'old_values' => !empty($oldValues) ? $oldValues : null,
                'new_values' => !empty($newValues) ? $newValues : null,
                'changes' => !empty($changes) ? $changes : null,
                'ip_address' => $request ? $request->ip() : null,
                'user_agent' => $request ? $request->userAgent() : null,
                'url' => $request ? $request->fullUrl() : null,
                'method' => $request ? $request->method() : null,
                'metadata' => !empty($metadataToStore) ? $metadataToStore : null,
            ]);
        } catch (\Exception $e) {
            // Log the error instead of failing silently
            Log::error('Failed to create audit log', [
                'user_id' => $user->id,
                'action_type' => $action_type,
                'channel' => $channel,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Re-throw in development to help debug, but suppress in production
            if (config('app.debug')) {
                throw $e;
            }
        }
    }
}
if (!function_exists('createPayslipRecord')) {
function createPayslipRecord($employee, $month, $process_id, $user_id, $file = null)
    {
        return
            Payslip::create([
                'user_id' => $user_id,
                'send_payslip_process_id' => $process_id,
                'employee_id' => $employee->id,
                'company_id' => $employee->company_id,
                'department_id' => $employee->department_id,
                'service_id' => $employee->service_id,
                'first_name' => $employee->first_name,
                'last_name' => $employee->last_name,
                'email' => $employee->email,
                'file' => $file,
                'phone' => !is_null($employee->professional_phone_number) ? $employee->professional_phone_number : $employee->personal_phone_number,
                'matricule' => $employee->matricule,
                'month' => $month,
                'year' => now()->year,
                'encryption_status' => Payslip::STATUS_SUCCESSFUL,
            ]);
    }
}
if (!function_exists('sendSmsAndUpdateRecord')) {
    function sendSmsAndUpdateRecord($emp, $month, $record, $sms_balance = null, $job_context = [])
    {
        // Check if employee has SMS notifications enabled
        if (isset($emp->receive_sms_notifications) && !$emp->receive_sms_notifications) {
            // Persist a note to explain the Disabled status without polluting failure_reason
            $record->update([
                'sms_sent_status' => Payslip::STATUS_DISABLED,
                'sms_status_note' => __('payslips.sms_notifications_disabled_for_this_employee'),
            ]);
            return;
        }

        $setting = Setting::first();

        if ( !empty($emp->professional_phone_number) || !empty($emp->personal_phone_number) ) {



            $phone = !empty($emp->professional_phone_number) ? $emp->professional_phone_number : $emp->personal_phone_number;

            if (!empty($month)) {
                $year = now()->year;
            } else {
                $year = '';
            }

             // Check if SMS provider is known to be unhealthy from balance check
             if (isset($job_context['sms_provider_healthy']) && $job_context['sms_provider_healthy'] === false) {
                 $error_message = $job_context['sms_provider_error'] ?? __('payslips.sms_provider_unhealthy');
                 Log::warning('Skipping SMS send - provider unhealthy', [
                     'employee_id' => $emp->id,
                     'employee_matricule' => $emp->matricule,
                     'error' => $error_message,
                     'job_context' => $job_context
                 ]);

                 // Preserve existing failure reason if any
                 $existingReason = !empty($record->failure_reason) ? $record->failure_reason . ' | ' : '';
                 $record->update([
                     'sms_sent_status' => Payslip::STATUS_FAILED,
                     'failure_reason' => $existingReason . __('payslips.sms_provider_unhealthy_during_balance_check') . ': ' . $error_message
                 ]);
                 return;
             }

             // Validate SMS provider credentials before creating client
             if (empty($setting->sms_provider_username) || empty($setting->sms_provider_password)) {
                 // Preserve existing failure reason if any
                 $existingReason = !empty($record->failure_reason) ? $record->failure_reason . ' | ' : '';
                 $record->update([
                     'sms_sent_status' => Payslip::STATUS_FAILED,
                     'failure_reason' => $existingReason . __('payslips.sms_provider_credentials_not_configured')
                 ]);
                 return;
             }

             try {
                 $sms_client = match ($setting->sms_provider) {
                     'twilio' => new TwilioSMS($setting),
                     'nexah' =>  new Nexah($setting),
                     'aws_sns' => new AwsSnsSMS($setting),
                     default => new Nexah($setting)
                 };
             } catch (\Throwable $e) {
                 Log::error('Failed to initialize SMS provider', [
                     'provider' => $setting->sms_provider,
                     'error' => $e->getMessage(),
                     'employee_id' => $emp->id,
                     'employee_matricule' => $emp->matricule,
                     'job_context' => $job_context
                 ]);

                 // Preserve existing failure reason if any
                 $existingReason = !empty($record->failure_reason) ? $record->failure_reason . ' | ' : '';
                 $record->update([
                     'sms_sent_status' => Payslip::STATUS_FAILED,
                     'failure_reason' => $existingReason . __('payslips.sms_provider_initialization_failed')
                 ]);
                 return;
             }

            $message = '';

            if($emp->preferred_language === 'en'){
                $message = str_replace([':name:', ':month:', ':year:', ':pdf_password:'], [trim($emp->name), $month, $year, $emp->pdf_password], $setting->sms_content_en);
            }else{
                $message = str_replace([':name:', ':month:', ':year:', ':pdf_password:'], [trim($emp->name), $month, $year, $emp->pdf_password], $setting->sms_content_fr);
            }

            try {
                $response = $sms_client->sendSMS([
                    'sms' =>  $message,
                    'mobiles' => $phone,
                ]);

                if ($response['responsecode'] === 1) {
                    $record->update(['sms_sent_status' => Payslip::STATUS_SUCCESSFUL]);
                } else {
                    // Check if failure is due to insufficient balance
                    $failureReason = __('payslips.failed_sending_sms');

                    // Use provided balance or check once per job if not provided
                    if ($sms_balance !== null) {
                        if ($sms_balance['credit'] === 0) {
                            $failureReason = __('payslips.insufficient_sms_balance');
                        }
                    } elseif (!empty($sms_client)) {
                        try {
                            $balance = $sms_client->getBalance();
                            if ($balance['credit'] === 0) {
                                $failureReason = __('payslips.insufficient_sms_balance');
                            }
                        } catch (\Throwable $balanceException) {
                            Log::warning('Failed to check SMS balance', [
                                'error' => $balanceException->getMessage(),
                                'employee_id' => $emp->id
                            ]);
                        }
                    }

                    // Preserve existing failure reason if any
                    $existingReason = !empty($record->failure_reason) ? $record->failure_reason . ' | ' : '';
                    $record->update([
                        'sms_sent_status' => Payslip::STATUS_FAILED,
                        'failure_reason' => $existingReason . $failureReason
                    ]);
                }
            } catch (\Throwable $e) {
                $error_details = [
                    'error' => $e->getMessage(),
                    'error_class' => get_class($e),
                    'employee_id' => $emp->id,
                    'employee_matricule' => $emp->matricule,
                    'phone' => $phone,
                    'provider' => $setting->sms_provider,
                    'month' => $month,
                    'message_length' => strlen($message),
                    'job_context' => $job_context
                ];

                // Add stack trace only if in debug mode or if it's a critical error
                if (config('app.debug') || strpos($e->getMessage(), 'Cannot assign null to property') !== false) {
                    $error_details['stack_trace'] = $e->getTraceAsString();
                }

                Log::error('SMS sending failed with exception', $error_details);

                // Preserve existing failure reason if any
                $existingReason = !empty($record->failure_reason) ? $record->failure_reason . ' | ' : '';

                // Create more specific error message based on exception type
                $error_message = __('payslips.sms_sending_exception') . ': ' . $e->getMessage();
                if (strpos($e->getMessage(), 'Cannot assign null to property') !== false) {
                    $error_message = __('payslips.sms_provider_configuration_error') . ': ' . __('payslips.null_value_in_provider_config');
                }

                $record->update([
                    'sms_sent_status' => Payslip::STATUS_FAILED,
                    'failure_reason' => $existingReason . $error_message
                ]);
            }
        } else {
            // Preserve existing failure reason if any
            $existingReason = !empty($record->failure_reason) ? $record->failure_reason . ' | ' : '';
            $record->update([
                'sms_sent_status' => Payslip::STATUS_FAILED,
                'failure_reason' => $existingReason . __('payslips.no_valid_phone_number_for_user')
            ]);
        }
    }
}
if (!function_exists('sendSmsBirthday')) {
    function sendSmsBirthday($emp)
    {
        $setting = Setting::first();

        if (!empty($emp->professional_phone_number) || !empty($emp->personal_phone_number)) {

            $phone = !empty($emp->professional_phone_number) ? $emp->professional_phone_number : $emp->personal_phone_number;


            $sms_client = match ($setting->sms_provider) {
                'twilio' => new TwilioSMS($setting),
                'nexah' =>  new Nexah($setting),
                'aws_sns' => new AwsSnsSMS($setting),
                default => new Nexah($setting)
            };

            $message = '';

            if ($emp->preferred_language === 'en') {
                $message = str_replace([':name:'], [trim($emp->name)], $setting->birthday_sms_message_en);
            } else {
                $message = str_replace([':name:'], [trim($emp->name)], $setting->birthday_sms_message_fr);
            }

            $response = $sms_client->sendSMS([
                'sms' =>  $message,
                'mobiles' => $phone,
            ]);

            if ($response['responsecode'] === 1) {
                Log::info(__('payslips.birthday_message_sent_successfully_to') . $emp->name);
            } else {
                Log::info(__('payslips.birthday_message_failed_to_send_to') . $emp->name);
            }
        } else {
            Log::info(__('payslips.no_valid_phone_number_for_user'));
        }
    }
}
if (!function_exists('countPages')) {
    function countPages(string $path): int
    {
        $pdf = file_get_contents($path);
        return preg_match_all("/\/Page\W/", $pdf, $dummy);
    }
}
if (!function_exists('setSavedSmtpCredentials')) {
    function setSavedSmtpCredentials(): void
    {
        // Debug: Log when this function is called
        \Log::info('setSavedSmtpCredentials called', [
            'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5)
        ]);

        $setting = Setting::first();

        if(!empty($setting)){
            Config::set('mail.mailers.smtp.host', $setting->smtp_host);
            Config::set('mail.mailers.smtp.port', (int) $setting->smtp_port);
            Config::set('mail.mailers.smtp.username', $setting->smtp_username);
            Config::set('mail.mailers.smtp.password', $setting->smtp_password);
            Config::set('mail.mailers.smtp.encryption', $setting->smtp_encryption);
            Config::set('mail.from.address', $setting->from_email);
            Config::set('mail.from.name', $setting->from_name);
            Config::set('mail.mailers.smtp.transport', !empty($setting->smtp_provider) ? $setting->smtp_provider : 'smtp');
        }

    }
}

if (!function_exists('cleanString')) {
    function cleanString($text)
    {
        $utf8 = array(
            '/[áàâãªä]/u'   =>   'a',
            '/[ÁÀÂÃÄ]/u'    =>   'A',
            '/[ÍÌÎÏ]/u'     =>   'I',
            '/[íìîï]/u'     =>   'i',
            '/[éèêë]/u'     =>   'e',
            '/[ÉÈÊË]/u'     =>   'E',
            '/[óòôõºö]/u'   =>   'o',
            '/[ÓÒÔÕÖ]/u'    =>   'O',
            '/[úùûü]/u'     =>   'u',
            '/[ÚÙÛÜ]/u'     =>   'U',
            '/ç/'           =>   'c',
            '/Ç/'           =>   'C',
            '/ñ/'           =>   'n',
            '/Ñ/'           =>   'N',
            '/–/'           =>   '-', // UTF-8 hyphen to "normal" hyphen
            '/[’‘‹›‚]/u'    =>   ' ', // Literally a single quote
            '/[“”«»„]/u'    =>   ' ', // Double quote
            '/ /'           =>   ' ', // nonbreaking space (equiv. to 0x160)
        );
        return preg_replace(array_keys($utf8), array_values($utf8), $text);
    }
}

if (!function_exists('numberFormat')) {
    function numberFormat($number, $decimal = 0): string
    {

        if ($number > 1000) {

            $x = round($number);
            $x_number_format = number_format($x);
            $x_array = explode(',', $x_number_format);
            $x_parts = array('K', 'M', 'B', 'T');
            $x_count_parts = count($x_array) - 1;
            $x_display = $x;
            $x_display = $x_array[0] . ((int) $x_array[1][0] !== 0 ? '.' . $x_array[1][0] : '');
            $x_display .= $x_parts[$x_count_parts - 1];

            return $x_display;
        }

        return $number;
    }
}

if (!function_exists('validatePhoneNumber')) {
    /**
     * Validate and format phone number to E.164 format
     * 
     * @param string $phoneNumber Phone number to validate
     * @param string|null $countryCode Optional country code to use if phone doesn't have one
     * @return array ['valid' => bool, 'formatted' => string|null, 'error' => string|null]
     */
    function validatePhoneNumber(string $phoneNumber, ?string $countryCode = null): array
    {
        if (empty(trim($phoneNumber))) {
            return [
                'valid' => false,
                'formatted' => null,
                'error' => __('common.phone_number_cannot_be_empty')
            ];
        }

        // Remove all whitespace and common formatting characters
        $phoneNumber = preg_replace('/[\s\-\(\)\.]/', '', $phoneNumber);
        
        // Remove all non-numeric characters except +
        $phoneNumber = preg_replace('/[^0-9+]/', '', $phoneNumber);

        // If phone already starts with +, validate it
        if (str_starts_with($phoneNumber, '+')) {
            $digits = substr($phoneNumber, 1);
            
            // Check if country code starts with 0 (invalid in E.164)
            if (str_starts_with($digits, '0')) {
                return [
                    'valid' => false,
                    'formatted' => null,
                    'error' => __('common.country_code_cannot_start_with_zero')
                ];
            }
            
            // Remove leading zeros (trunk prefixes)
            $originalDigits = $digits;
            $digits = ltrim($digits, '0');
            
            if (empty($digits)) {
                return [
                    'valid' => false,
                    'formatted' => null,
                    'error' => __('common.phone_number_must_contain_non_zero_digit')
                ];
            }
            
            $formatted = '+' . $digits;
            
            // Validate E.164 format
            if (!isValidE164Format($formatted)) {
                return [
                    'valid' => false,
                    'formatted' => null,
                    'error' => __('common.invalid_phone_number_format_e164')
                ];
            }
            
            return [
                'valid' => true,
                'formatted' => $formatted,
                'error' => null
            ];
        }

        // Handle phone numbers without + prefix
        $phoneNumber = ltrim($phoneNumber, '0');
        if (empty($phoneNumber)) {
            return [
                'valid' => false,
                'formatted' => null,
                'error' => __('common.phone_number_cannot_be_all_zeros')
            ];
        }

        // Use provided country code or try to detect
        if ($countryCode !== null && is_numeric($countryCode) && strlen($countryCode) <= 3) {
            $formatted = '+' . $countryCode . $phoneNumber;
        } else {
            // Try to detect country code
            $detectedCode = detectCountryCodeFromPhone($phoneNumber);
            if ($detectedCode !== null) {
                $formatted = '+' . $detectedCode . $phoneNumber;
            } else {
                // Just add + prefix (may fail if country code is required)
                $formatted = '+' . $phoneNumber;
            }
        }

        // Validate final format
        if (!isValidE164Format($formatted)) {
            return [
                'valid' => false,
                'formatted' => null,
                'error' => __('common.invalid_phone_number_format_e164')
            ];
        }

        return [
            'valid' => true,
            'formatted' => $formatted,
            'error' => null
        ];
    }
}

if (!function_exists('isValidE164Format')) {
    /**
     * Validate phone number is in E.164 format
     * 
     * @param string $phoneNumber Phone number to validate
     * @return bool True if valid E.164 format
     */
    function isValidE164Format(string $phoneNumber): bool
    {
        // Must start with +
        if (!str_starts_with($phoneNumber, '+')) {
            return false;
        }

        // Get digits after +
        $digits = substr($phoneNumber, 1);

        // Must contain only digits
        if (empty($digits) || !ctype_digit($digits)) {
            return false;
        }

        // E.164 allows 1-15 digits after the +
        $digitCount = strlen($digits);
        if ($digitCount < 1 || $digitCount > 15) {
            return false;
        }

        return true;
    }
}

if (!function_exists('detectCountryCodeFromPhone')) {
    /**
     * Detect country code from phone number
     * 
     * @param string $phoneNumber Phone number without + prefix
     * @return string|null Detected country code or null
     */
    function detectCountryCodeFromPhone(string $phoneNumber): ?string
    {
        $commonCountryCodes = [
            '1' => ['US', 'CA'],
            '33' => ['FR'],
            '44' => ['GB'],
            '237' => ['CM'],
            '225' => ['CI'],
            '226' => ['BF'],
            '229' => ['BJ'],
            '242' => ['CG'],
            '243' => ['CD'],
            '236' => ['CF'],
        ];

        foreach ($commonCountryCodes as $code => $countries) {
            if (str_starts_with($phoneNumber, $code)) {
                $remainingDigits = substr($phoneNumber, strlen($code));
                if (strlen($remainingDigits) >= 7 && strlen($remainingDigits) <= 12) {
                    return $code;
                }
            }
        }

        return null;
    }
}

if (!function_exists('findOrCreateDepartment')) {
    /**
     * Find department by name or create if allowed
     *
     * @param string $departmentName Department name to find/create
     * @param int $companyId Company ID
     * @param bool $autoCreate Whether to auto-create if not found
     * @return array ['found' => bool, 'department' => Department|null, 'error' => string|null]
     */
    function findOrCreateDepartment(string $departmentName, int $companyId, bool $autoCreate = false): array
    {
        if (empty(trim($departmentName))) {
            return [
                'found' => false,
                'department' => null,
                'error' => __('common.department_name_cannot_be_empty')
            ];
        }

        $department = \App\Models\Department::where('company_id', $companyId)
            ->where('name', trim($departmentName))
            ->first();

        if ($department) {
            return [
                'found' => true,
                'department' => $department,
                'error' => null
            ];
        }

        if (!$autoCreate) {
            return [
                'found' => false,
                'department' => null,
                'error' => __('common.department_not_found_in_company', [
                    'name' => $departmentName,
                    'departments' => \App\Models\Department::where('company_id', $companyId)->pluck('name')->join(', ')
                ])
            ];
        }

        try {
            $department = \App\Models\Department::create([
                'name' => trim($departmentName),
                'company_id' => $companyId,
                'author_id' => auth()->id(),
            ]);

            return [
                'found' => true,
                'department' => $department,
                'error' => null
            ];
        } catch (\Exception $e) {
            return [
                'found' => false,
                'department' => null,
                'error' => __('common.failed_to_create_department', [
                    'name' => $departmentName,
                    'error' => $e->getMessage()
                ])
            ];
        }
    }
}

if (!function_exists('findOrCreateService')) {
    /**
     * Find service by name or create if allowed
     *
     * @param string $serviceName Service name to find/create
     * @param int $departmentId Department ID
     * @param int $companyId Company ID
     * @param bool $autoCreate Whether to auto-create if not found
     * @return array ['found' => bool, 'service' => Service|null, 'error' => string|null]
     */
    function findOrCreateService(string $serviceName, int $departmentId, int $companyId, bool $autoCreate = false): array
    {
        if (empty(trim($serviceName))) {
            return [
                'found' => false,
                'service' => null,
                'error' => __('common.service_name_cannot_be_empty')
            ];
        }

        $service = \App\Models\Service::where('department_id', $departmentId)
            ->where('name', trim($serviceName))
            ->first();

        if ($service) {
            return [
                'found' => true,
                'service' => $service,
                'error' => null
            ];
        }

        if (!$autoCreate) {
            return [
                'found' => false,
                'service' => null,
                'error' => __('common.service_not_found_in_department', [
                    'name' => $serviceName,
                    'services' => \App\Models\Service::where('department_id', $departmentId)->pluck('name')->join(', ')
                ])
            ];
        }

        try {
            $service = \App\Models\Service::create([
                'name' => trim($serviceName),
                'department_id' => $departmentId,
                'company_id' => $companyId,
                'author_id' => auth()->id(),
            ]);

            return [
                'found' => true,
                'service' => $service,
                'error' => null
            ];
        } catch (\Exception $e) {
            return [
                'found' => false,
                'service' => null,
                'error' => __('common.failed_to_create_service', [
                    'name' => $serviceName,
                    'error' => $e->getMessage()
                ])
            ];
        }
    }
}

if (!function_exists('findOrCreateCompany')) {
    /**
     * Find company by name or create if allowed
     *
     * @param string $companyName Company name to find/create
     * @param bool $autoCreate Whether to auto-create if not found
     * @return array ['found' => bool, 'company' => Company|null, 'error' => string|null]
     */
    function findOrCreateCompany(string $companyName, bool $autoCreate = false): array
    {
        if (empty(trim($companyName))) {
            return [
                'found' => false,
                'company' => null,
                'error' => __('common.company_name_cannot_be_empty')
            ];
        }

        $company = \App\Models\Company::where('name', trim($companyName))->first();

        if ($company) {
            return [
                'found' => true,
                'company' => $company,
                'error' => null
            ];
        }

        if (!$autoCreate) {
            return [
                'found' => false,
                'company' => null,
                'error' => __('common.company_not_found', [
                    'name' => $companyName,
                    'companies' => \App\Models\Company::pluck('name')->join(', ')
                ])
            ];
        }

        try {
            $company = \App\Models\Company::create([
                'name' => trim($companyName),
                'author_id' => auth()->id(),
            ]);

            return [
                'found' => true,
                'company' => $company,
                'error' => null
            ];
        } catch (\Exception $e) {
            return [
                'found' => false,
                'company' => null,
                'error' => __('common.failed_to_create_company', [
                    'name' => $companyName,
                    'error' => $e->getMessage()
                ])
            ];
        }
    }
}

if (!function_exists('findDepartmentByName')) {
    /**
     * Find department by name with fuzzy matching
     *
     * @param string $departmentName Department name to find
     * @param int $companyId Company ID
     * @return array ['found' => bool, 'department' => Department|null, 'suggestions' => array, 'error' => string|null]
     */
    function findDepartmentByName(string $departmentName, int $companyId): array
    {
        if (empty(trim($departmentName))) {
            return [
                'found' => false,
                'department' => null,
                'suggestions' => [],
                'error' => __('common.department_name_cannot_be_empty')
            ];
        }

        $departments = \App\Models\Department::where('company_id', $companyId)->get();

        // Exact match
        $exactMatch = $departments->first(fn($dept) => strcasecmp($dept->name, trim($departmentName)) === 0);
        if ($exactMatch) {
            return [
                'found' => true,
                'department' => $exactMatch,
                'suggestions' => [],
                'error' => null
            ];
        }

        // Fuzzy matching
        $suggestions = [];
        $searchName = strtolower(trim($departmentName));

        foreach ($departments as $dept) {
            $deptName = strtolower($dept->name);
            $similarity = 0;

            // Calculate similarity
            similar_text($searchName, $deptName, $similarity);

            if ($similarity >= 60) { // 60% similarity threshold
                $suggestions[] = [
                    'name' => $dept->name,
                    'similarity' => round($similarity, 1)
                ];
            }
        }

        // Sort suggestions by similarity
        usort($suggestions, fn($a, $b) => $b['similarity'] <=> $a['similarity']);

        return [
            'found' => false,
            'department' => null,
            'suggestions' => array_slice($suggestions, 0, 3), // Top 3 suggestions
            'error' => __('common.department_not_found', [
                'name' => $departmentName,
                'suggestions' => !empty($suggestions)
                    ? __('common.did_you_mean', ['names' => collect($suggestions)->pluck('name')->join(', ')])
                    : __('common.available_departments', ['names' => $departments->pluck('name')->join(', ')])
            ])
        ];
    }
}

if (!function_exists('findServiceByName')) {
    /**
     * Find service by name with fuzzy matching
     *
     * @param string $serviceName Service name to find
     * @param int $departmentId Department ID
     * @return array ['found' => bool, 'service' => Service|null, 'suggestions' => array, 'error' => string|null]
     */
    function findServiceByName(string $serviceName, int $departmentId): array
    {
        if (empty(trim($serviceName))) {
            return [
                'found' => false,
                'service' => null,
                'suggestions' => [],
                'error' => __('common.service_name_cannot_be_empty')
            ];
        }

        $services = \App\Models\Service::where('department_id', $departmentId)->get();

        // Exact match
        $exactMatch = $services->first(fn($svc) => strcasecmp($svc->name, trim($serviceName)) === 0);
        if ($exactMatch) {
            return [
                'found' => true,
                'service' => $exactMatch,
                'suggestions' => [],
                'error' => null
            ];
        }

        // Fuzzy matching
        $suggestions = [];
        $searchName = strtolower(trim($serviceName));

        foreach ($services as $svc) {
            $svcName = strtolower($svc->name);
            $similarity = 0;

            similar_text($searchName, $svcName, $similarity);

            if ($similarity >= 60) { // 60% similarity threshold
                $suggestions[] = [
                    'name' => $svc->name,
                    'similarity' => round($similarity, 1)
                ];
            }
        }

        // Sort suggestions by similarity
        usort($suggestions, fn($a, $b) => $b['similarity'] <=> $a['similarity']);

        return [
            'found' => false,
            'service' => null,
            'suggestions' => array_slice($suggestions, 0, 3), // Top 3 suggestions
            'error' => __('common.service_not_found', [
                'name' => $serviceName,
                'suggestions' => !empty($suggestions)
                    ? __('common.did_you_mean', ['names' => collect($suggestions)->pluck('name')->join(', ')])
                    : __('common.available_services', ['names' => $services->pluck('name')->join(', ')])
            ])
        ];
    }
}

if (!function_exists('translateMonthName')) {
    /**
     * Translate English month name to current locale
     *
     * @param string $englishMonth English month name (e.g., 'January')
     * @return string Translated month name
     */
    function translateMonthName(string $englishMonth): string
    {
        $monthMap = [
            'january' => 'january',
            'february' => 'february',
            'march' => 'march',
            'april' => 'april',
            'may' => 'may',
            'june' => 'june',
            'july' => 'july',
            'august' => 'august',
            'september' => 'september',
            'october' => 'october',
            'november' => 'november',
            'december' => 'december',
        ];

        $monthKey = strtolower($englishMonth);
        return isset($monthMap[$monthKey]) ? __('common.' . $monthMap[$monthKey]) : $englishMonth;
    }
}

if (!function_exists('validateEmail')) {
    /**
     * Validate email address with comprehensive checks
     *
     * @param string $email Email address to validate
     * @return array ['valid' => bool, 'error' => string|null]
     */
    function validateEmail(string $email): array
    {
        if (empty(trim($email))) {
            return [
                'valid' => false,
                'error' => __('common.email_address_cannot_be_empty')
            ];
        }

        // Check email length (RFC 5321: 320 characters max) BEFORE format check so we can return a specific error
        if (strlen($email) > 320) {
            return [
                'valid' => false,
                'error' => __('common.email_address_is_too_long', ['maximum' => 320])
            ];
        }

        // Split email into local and domain parts
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return [
                'valid' => false,
                'error' => __('common.invalid_email_address_format')
            ];
        }

        [$localPart, $domain] = $parts;

        // Validate local part (before @)
        if (empty($localPart) || strlen($localPart) > 64) {
            return [
                'valid' => false,
                'error' => __('common.email_local_part_is_invalid_or_too_long')
            ];
        }

        // Validate domain part
        if (empty($domain) || strlen($domain) > 255) {
            return [
                'valid' => false,
                'error' => __('common.email_domain_is_invalid_or_too_long')
            ];
        }

        // Check for consecutive dots
        if (strpos($localPart, '..') !== false || strpos($domain, '..') !== false) {
            return [
                'valid' => false,
                'error' => __('common.email_address_cannot_contain_consecutive_dots')
            ];
        }

        // Check domain has at least one dot (has TLD)
        if (strpos($domain, '.') === false) {
            return [
                'valid' => false,
                'error' => __('common.email_domain_must_contain_a_top_level_domain')
            ];
        }

        // Additional domain validation
        if (!preg_match('/^[a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?(\.[a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?)*$/', $domain)) {
            return [
                'valid' => false,
                'error' => __('common.email_domain_format_is_invalid')
            ];
        }

        // Basic format validation (place after specific checks to yield test-expected messages)
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'valid' => false,
                'error' => __('common.invalid_email_address_format')
            ];
        }

        return [
            'valid' => true,
            'error' => null
        ];
    }
}
