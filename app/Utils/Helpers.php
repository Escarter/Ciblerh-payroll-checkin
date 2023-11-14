<?php 

use App\Models\User;
use App\Models\Setting;
use App\Services\Nexah;
use App\Models\AuditLog;
use App\Services\TwilioSMS;
use Illuminate\Support\Facades\Config;
use App\Models\Payslip;

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
    function auditLog(User $user, string $action_type, string $channel, string $action_performed)
    {
        AuditLog::create([
            'user_id' => $user->id,
            'user' => $user->name,
            'action_type' => $action_type,
            'channel' => $channel,
            'action_perform' => $action_performed,
            'company_id' => $user->company_id,
            'department_id' => $user->department_id ,
            'author_id' => $user->author_id ,
        ]);
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
                
            ]);
    }
}
if (!function_exists('sendSmsAndUpdateRecord')) {
    function sendSmsAndUpdateRecord($emp, $month, $record)
    {
        $setting = Setting::first();

        if ( !empty($emp->professional_phone_number) || !empty($emp->personal_phone_number) ) {

            $phone = !empty($emp->professional_phone_number) ? $emp->professional_phone_number : $emp->personal_phone_number;

            if (!empty($month)) {
                $year = now()->year;
            } else {
                $year = '';
            }

             $sms_client = match ($setting->sms_provider) {
                'twilio' => new TwilioSMS($setting),
                'nexah' =>  new Nexah($setting),
                default => new Nexah($setting)
             };

            $message = '';

            if($emp->preferred_language === 'en'){
                $message = str_replace([':name:', ':month:', ':year:', ':pdf_password:'], [trim($emp->name), $month, $year, $emp->pdf_password], $setting->sms_content_en);
            }else{
                $message = str_replace([':name:', ':month:', ':year:', ':pdf_password:'], [trim($emp->name), $month, $year, $emp->pdf_password], $setting->sms_content_fr);
            }

            $response = $sms_client->sendSMS([
                    'sms' =>  $message,
                    'mobiles' => $phone,
                ]);

            if ($response['responsecode'] === 1) {
                $record->update(['sms_sent_status' => Payslip::STATUS_SUCCESSFUL]);
            } else {
                $record->update(['sms_sent_status' => Payslip::STATUS_FAILED, 'failure_reason' => __('Failed sending SMS')]);
            }
        } else {
            $record->update(['sms_sent_status' => Payslip::STATUS_FAILED, 'failure_reason' => __('No valid phone number for user')]);
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
        $setting = Setting::first();

        if(!empty($setting)){
            Config::set('mail.mailers.smtp.host', $setting->smtp_host);
            Config::set('mail.mailers.smtp.port', $setting->smtp_port);
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