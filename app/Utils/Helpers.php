<?php 

use App\Models\User;
use App\Models\AuditLog;

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

if (!function_exists('sendSms')) {
    function sendSms($emp, $month = '')
    {
        $phone = !empty($emp->professional_phone_number) ? $emp->professional_phone_number : $emp->personal_phone_number;

        if (!empty($month)) {
            $year = now()->year;
        } else {
            $year = '';
        }

        $message = "Mr/Mme " . trim($emp->name) . ", votre bulletin de paie du mois de {$month}-{$year} a été envoyé dans votre boite mail. Veuillez utiliser le mot de passe suivant : {$emp->pdf_password} pour le consulter. ";
        $endpoint = "https://sms.etech-keys.com/ss/api.php?login=691911568&password=ciblerh&sender_id=CibleRH&destinataire={$phone}&message={$message}&ext_id=" . Str::random(10) . "&programmation=0";

        return Http::get($endpoint);
    }
}
if (!function_exists('sendSmsAndUpdateRecord')) {
    function sendSmsAndUpdateRecord($emp, $month, $record)
    {
        if (
            !empty($emp->professional_phone_number) ||
            !empty($emp->personal_phone_number)
        ) {
            // global sendSms method found in helper.php
            $response = sendSms($emp, $month);

            if ($response->ok()) {
                $record->update(['sms_sent_status' => 'successful']);
            } else {
                $record->update(['sms_sent_status' => 'failed', 'failure_reason' => __('Failed sending SMS')]);
            }
        } else {
            $record->update(['sms_sent_status' => 'failed', 'failure_reason' => __('No valid phone number for user')]);
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