<?php

namespace App\Livewire\Portal\Settings;

use App\Mail\TestEmail;
use App\Models\Setting;
use App\Services\Nexah;
use Livewire\Component;
use App\Services\TwilioSMS;
use App\Services\AwsSnsSMS;
use App\Services\OrangeCameroonSMS;
use Illuminate\Support\Facades\Mail;
use App\Livewire\Traits\WithDataTable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class Index extends Component
{
    use WithDataTable;

    public $setting, $sms_provider, $sms_provider_username, $sms_provider_password, $sms_provider_senderid;

    // Provider-specific SMS properties for better field alignment
    public $nexah_username, $nexah_password, $nexah_senderid;
    public $twilio_account_sid, $twilio_auth_token, $twilio_phone_number;
    public $sns_access_key, $sns_secret_key, $sns_region, $sns_senderid;
    public $orange_cm_application_id, $orange_cm_client_id, $orange_cm_client_secret, $orange_cm_sender_address;

    /** Messaging Pro Cameroon (api.orange.cm) — separate from OAuth Client ID/Secret */
    public $orange_cm_msp_username;

    public $orange_cm_msp_password;

    public $smtp_provider;
    public $mailgun_domain;
    public $mailgun_secret;
    public $mailgun_endpoint;
    public $mailgun_scheme;
    public $smtp_host;
    public $smtp_port;
    public $smtp_username;
    public $smtp_password;
    public $smtp_encryption;

    // AWS SES
    public $ses_key;
    public $ses_secret;
    public $ses_region;

    // Postmark
    public $postmark_token;

    // Sendmail
    public $sendmail_path;

    // Mailpit
    public $mailpit_host;
    public $mailpit_port;

    // Log
    public $log_channel;

    // Mailchimp Transactional (Mandrill)
    public $mailchimp_api_key;
    public $from_email;
    public $from_name;
    public $replyTo_email;
    public $replyTo_name;
    public $test_email_address;
    public $test_email_message;
    public $test_phone_number;
    public $test_sms_message;
    /** @var int|null Null when provider does not expose a numeric balance (e.g. Orange Messaging Pro). */
    public $sms_balance = null;
    public $sms_content_en;
    public $sms_content_fr;
    public $email_content_en ;
    public $email_content_fr;
    public $email_subject_en ;
    public $email_subject_fr;
    public $welcome_email_content_en ;
    public $welcome_email_content_fr;
    public $welcome_email_subject_en ;
    public $welcome_email_subject_fr;
    public $birthday_sms_message_en;
    public $birthday_sms_message_fr;

    // Feature Configuration Properties
    public $inactivity_deactivation_enabled = false;
    public $inactivity_months_threshold = 6;
    public $deactivation_check_time = '02:00';

    public $sftp_sync_enabled = false;
    // Legacy single-user props kept for backward-compat display only (HTTP API fallback)
    public $sftp_push_username;
    public $sftp_push_password;
    public $sftp_push_path = 'storage/app/sftp-push';
    public $sftp_sync_frequency = 'daily';
    public $sftp_push_scan_frequency = 'everyFiveMinutes';
    public $sftp_push_scan_days = [];
    public $sftp_push_scan_time = '00:00';
    public $sftp_push_archive_frequency = '';
    public $sftp_push_archive_days = [];
    public $sftp_push_archive_time = '00:00';
    public $sftp_push_archive_min_age_minutes = 5;
    public $sftp_push_archive_move_processed = true;
    public $sftp_push_archive_move_rejected = true;
    public $sftp_push_archive_move_failed = false;
    public $sftp_push_archive_require_successful_process = true;
    public $sftp_matching_strategies = [];
    public $sftp_connection_status = false;
    public $test_sftp_message;

    // Multi-user SFTP: list of SftpUser records (as plain arrays for Livewire).
    // Each entry includes pre-generated 'script', 'host', 'port' keys for client-side Alpine display.
    public $sftpUsers = [];
    // Holds the id of the SFTP user pending deletion (set by confirmRemoveSftpUser, consumed by delete)
    public $sftpUserToDelete = null;

    // Auto-match configuration
    public $sftp_auto_match_enabled = false;
    public $sftp_auto_match_threshold = 80;
    public $sftp_auto_match_min_strategy = 'reverse_partial_match';
    public $sftp_auto_match_notification_email = '';

    // Proposal created notification
    public $sftp_match_created_notification_enabled = false;
    public $sftp_match_created_notification_email = '';

    // SFTP OS user (real SFTP/SSH client access) — kept for legacy single-user script display
    public $sftp_os_username;
    public $sftp_os_password;
    public $sftp_server_host;
    public $sftp_server_port = 22;

    /** One-time display of generated push credentials */
    public $sftp_generated_username_display = null;
    public $sftp_generated_password_display = null;
    public $show_push_password = false;
    public $sftp_os_generated_display = null; // legacy single-user OS script display

    public function mount() {

        $this->setting = $this->resolveSftpSetting();

        $this->sms_provider= !empty($this->setting) ? $this->setting->sms_provider: '';
        $this->sms_provider_username = !empty($this->setting) ? $this->setting->sms_provider_username : '';
        $this->sms_provider_password = !empty($this->setting) ? $this->setting->sms_provider_password :'';
        $this->sms_provider_senderid = !empty($this->setting) ? $this->setting->sms_provider_senderid :'';
        $this->orange_cm_msp_username = ! empty($this->setting) ? ($this->setting->sms_msp_username ?? '') : '';
        $this->orange_cm_msp_password = ! empty($this->setting) ? ($this->setting->sms_msp_password ?? '') : '';
        $this->smtp_provider = !empty($this->setting) ? $this->setting->smtp_provider :'smtp';
        $this->smtp_host = !empty($this->setting) ? $this->setting->smtp_host :'';
        $this->smtp_port = !empty($this->setting) ? $this->setting->smtp_port :'';
        $this->smtp_username = !empty($this->setting) ? $this->setting->smtp_username :'';
        $this->smtp_password = !empty($this->setting) ? $this->setting->smtp_password :'';
        $this->smtp_encryption = !empty($this->setting) ? $this->setting->smtp_encryption :'';
        $this->from_email = !empty($this->setting) ? $this->setting->from_email :'';
        $this->from_name = !empty($this->setting) ? $this->setting->from_name :'';
        $this->replyTo_email = !empty($this->setting) ? $this->setting->replyTo_email :'';
        $this->replyTo_name = !empty($this->setting) ? $this->setting->replyTo_name :'';

        // Load additional provider settings
        $this->mailgun_domain = !empty($this->setting) ? $this->setting->mailgun_domain :'';
        $this->mailgun_secret = !empty($this->setting) ? $this->setting->mailgun_secret :'';
        $this->mailgun_endpoint = !empty($this->setting) ? $this->setting->mailgun_endpoint :'';
        $this->mailgun_scheme = !empty($this->setting) ? $this->setting->mailgun_scheme :'';
        $this->ses_key = !empty($this->setting) ? $this->setting->ses_key :'';
        $this->ses_secret = !empty($this->setting) ? $this->setting->ses_secret :'';
        $this->ses_region = !empty($this->setting) ? $this->setting->ses_region :'';
        $this->postmark_token = !empty($this->setting) ? $this->setting->postmark_token :'';
        $this->sendmail_path = !empty($this->setting) ? $this->setting->sendmail_path :'';
        $this->mailpit_host = !empty($this->setting) ? $this->setting->mailpit_host :'';
        $this->mailpit_port = !empty($this->setting) ? $this->setting->mailpit_port :'';
        $this->log_channel = !empty($this->setting) ? $this->setting->log_channel :'';
        $this->mailchimp_api_key = !empty($this->setting) ? $this->setting->mailchimp_api_key :'';
        $this->sms_balance = $this->setting ? $this->setting->sms_balance : null;

        $this->sms_content_en = !empty($this->setting) ? (!empty($this->setting->sms_content_en) ? $this->setting->sms_content_en  : "Mr/Mrs :name:, your pay slip for the month of :month:-:year: has been sent to your mailbox. Please use the following password: :pdf_password: to view it.") :'';
        $this->sms_content_fr = !empty($this->setting) ? (!empty($this->setting->sms_content_fr) ? $this->setting->sms_content_fr : "M./Mme :name:, votre fiche de paie du mois de :month:-:year: a été envoyée dans votre boîte mail. Merci d'utiliser le mot de passe suivant : :pdf_password: pour la consulter."):'';
        $this->email_subject_en = !empty($this->setting) ? (!empty($this->setting->email_subject_en) ? $this->setting->email_subject_en  : "Your :month: :year: payslip.") :'';
        $this->email_subject_fr = !empty($this->setting) ? (!empty($this->setting->email_subject_fr) ? $this->setting->email_subject_fr : "Votre fiche de salaire :month: :year:."):'';
        $this->email_content_en = !empty($this->setting) ? (!empty($this->setting->email_content_en) ? $this->setting->email_content_en : "<h2>Dear :name:,</h2> <p>Please find your pay slip attached,</p> <p>How to open your pay slip:</p> <p>Download the PDF document attached to the email. You will be asked for your password</p><p>Enter the password received by SMS</p> <p>In case of difficulty, please call us or write to us using the contact details below:</p> <p>Call and text: :support_number:</p> <p>Mail: :mail_address:</p>") : '';
        $this->email_content_fr = !empty($this->setting) ? (!empty($this->setting->email_content_fr) ? $this->setting->email_content_fr : "<h2>Cher :name:,</h2> <p>Veuillez trouver votre fiche de paie en pièce jointe,</p> <p>Comment ouvrir votre fiche de paie :</p> <p>Téléchargez le document PDF joint au e-mail. Votre mot de passe vous sera demandé</p> <p>Saisissez le mot de passe reçu par SMS</p> <p>En cas de difficulté, merci de nous appeler ou de nous écrire aux coordonnées ci-dessous :</p> <p>Appel et SMS : support_number :</p> <p>Mail : mail_address :</p>") :'';

        $this->welcome_email_subject_en = !empty($this->setting) ? (!empty($this->setting->welcome_email_subject_en) ? $this->setting->welcome_email_subject_en : "CibleRh - Login Credentials") :'';
        $this->welcome_email_subject_fr = !empty($this->setting) ? (!empty($this->setting->welcome_email_subject_fr) ? $this->setting->welcome_email_subject_fr :  "CibleRh - Identifiants de connexion") :'';
        $this->welcome_email_content_en = !empty($this->setting) ? (!empty($this->setting->welcome_email_content_en) ? $this->setting->welcome_email_content_en : "<h3>Dear :name:,</h3> <p>Your account has been created and you can now login into the employee portal at, :site_url: your credentials are </p> <strong>Username :username:</strong> <br><strong>Password :password:</strong><p></p>  <p>In case of any difficulties, Contact your support via </p> <p>Call and text: :support_number:</p> <p>Mail: :mail_address:</p>") : '';
        $this->welcome_email_content_fr = !empty($this->setting) ? (!empty($this->setting->welcome_email_content_fr) ? $this->setting->welcome_email_content_fr : "<h2>Cher :name:,</h2> <p>Votre compte a été créé et vous pouvez désormais vous connecter au portail des employés sur,:site_url: vos identifiants sont </p> <strong>Nom d'utilisateur :username:</strong> <br><strong>Mot de passe :password:</strong><p></p> <p>En cas de difficultés, contactez votre support via </p> <p>Appel et SMS : :support_number:</p> <p>Mail : :mail_address:</p>") :'';

        $this->birthday_sms_message_en = !empty($this->setting) ? (!empty($this->setting->birthday_sms_message_en) ? $this->setting->birthday_sms_message_en : "Happy Birthday! :name:, Wishing you a fantastic day filled with joy and a year ahead full of success. Enjoy your special day!") :'';
        $this->birthday_sms_message_fr = !empty($this->setting) ? (!empty($this->setting->birthday_sms_message_fr) ? $this->setting->birthday_sms_message_fr : "Joyeux anniversaire! :name:, Je te souhaite une journée fantastique pleine de joie et une année à venir remplie de succès. Profite bien de ta journée spéciale!") :'';

        // Feature Configuration initialization
        $this->inactivity_deactivation_enabled = $this->setting ? (bool) $this->setting->inactivity_deactivation_enabled : false;
        $this->inactivity_months_threshold = !empty($this->setting) ? $this->setting->inactivity_months_threshold : 6;
        $this->deactivation_check_time = !empty($this->setting) ? $this->setting->deactivation_check_time : '02:00';

        $this->sftp_sync_enabled = $this->setting ? (bool) $this->setting->sftp_sync_enabled : false;
        $this->sftp_push_username = !empty($this->setting) ? $this->setting->sftp_push_username : '';
        $this->sftp_push_password = !empty($this->setting) ? $this->setting->sftp_push_password : '';
        $this->sftp_push_path = !empty($this->setting) ? $this->setting->sftp_push_path : 'storage/app/sftp-push';
        $this->sftp_sync_frequency = !empty($this->setting) ? $this->setting->sftp_sync_frequency : 'daily';
        $this->sftp_push_scan_frequency = !empty($this->setting) ? ($this->setting->sftp_push_scan_frequency ?? 'everyFiveMinutes') : 'everyFiveMinutes';
        $this->sftp_push_scan_days = !empty($this->setting) && !empty($this->setting->sftp_push_scan_days)
            ? explode(',', $this->setting->sftp_push_scan_days)
            : [];
        $this->sftp_push_scan_time = !empty($this->setting) ? ($this->setting->sftp_push_scan_time ?? '00:00') : '00:00';
        $this->sftp_push_archive_frequency = !empty($this->setting) ? ($this->setting->sftp_push_archive_frequency ?? '') : '';
        $this->sftp_push_archive_days = !empty($this->setting) && !empty($this->setting->sftp_push_archive_days)
            ? explode(',', $this->setting->sftp_push_archive_days)
            : [];
        $this->sftp_push_archive_time = !empty($this->setting) ? ($this->setting->sftp_push_archive_time ?? '00:00') : '00:00';
        $this->sftp_push_archive_min_age_minutes = !empty($this->setting)
            ? (int) ($this->setting->sftp_push_archive_min_age_minutes ?? 5)
            : 5;
        $this->sftp_push_archive_move_processed = !empty($this->setting)
            ? (bool) ($this->setting->sftp_push_archive_move_processed ?? true)
            : true;
        $this->sftp_push_archive_move_rejected = !empty($this->setting)
            ? (bool) ($this->setting->sftp_push_archive_move_rejected ?? true)
            : true;
        $this->sftp_push_archive_move_failed = !empty($this->setting)
            ? (bool) ($this->setting->sftp_push_archive_move_failed ?? false)
            : false;
        $this->sftp_push_archive_require_successful_process = !empty($this->setting)
            ? (bool) ($this->setting->sftp_push_archive_require_successful_process ?? true)
            : true;
        $this->sftp_matching_strategies = !empty($this->setting) && !empty($this->setting->sftp_matching_strategies) 
            ? $this->setting->sftp_matching_strategies 
            : [];

        // Auto-match configuration
        $this->sftp_auto_match_enabled = $this->setting ? (bool) $this->setting->sftp_auto_match_enabled : false;
        $this->sftp_auto_match_threshold = !empty($this->setting) && $this->setting->sftp_auto_match_threshold !== null
            ? (int) $this->setting->sftp_auto_match_threshold
            : 80;
        $this->sftp_auto_match_min_strategy = !empty($this->setting) ? ($this->setting->sftp_auto_match_min_strategy ?? 'reverse_partial_match') : 'reverse_partial_match';
        $this->sftp_auto_match_notification_email = !empty($this->setting) ? ($this->setting->sftp_auto_match_notification_email ?? '') : '';

        // Proposal created notification
        $this->sftp_match_created_notification_enabled = $this->setting ? (bool) $this->setting->sftp_match_created_notification_enabled : false;
        $this->sftp_match_created_notification_email = !empty($this->setting) ? ($this->setting->sftp_match_created_notification_email ?? '') : '';

        // SFTP OS user
        $this->sftp_os_username = !empty($this->setting) ? ($this->setting->sftp_os_username ?? '') : '';
        $this->sftp_os_password = !empty($this->setting) ? ($this->setting->sftp_os_password ?? '') : '';
        $this->sftp_server_host = !empty($this->setting) ? ($this->setting->sftp_server_host ?? '') : '';
        $this->sftp_server_port = !empty($this->setting) ? ($this->setting->sftp_server_port ?? 22) : 22;
        if (empty($this->sftp_server_host)) {
            $this->sftp_server_host = request()->getHost();
        }

        // Load multi-user SFTP users
        $this->loadSftpUsers();

        // Check if SFTP push is already configured
        $this->checkSftpConnectionStatus();

        // Initialize provider-specific properties based on current provider (after all properties are loaded)
        $this->initializeProviderSpecificProperties();

    }

    private function initializeProviderSpecificProperties()
    {
        // Initialize provider-specific properties based on the current SMS provider
        switch ($this->sms_provider) {
            case 'nexah':
                $this->nexah_username = $this->sms_provider_username;
                $this->nexah_password = $this->sms_provider_password;
                $this->nexah_senderid = $this->sms_provider_senderid;
                break;

            case 'twilio':
                $this->twilio_account_sid = $this->sms_provider_username;
                $this->twilio_auth_token = $this->sms_provider_password;
                $this->twilio_phone_number = $this->sms_provider_senderid;
                break;

            case 'aws_sns':
                $this->sns_access_key = $this->sms_provider_username;
                $this->sns_secret_key = $this->sms_provider_password;
                $this->sns_region = $this->ses_region ?: 'us-east-1'; // Default to us-east-1 if not set
                $this->sns_senderid = $this->sms_provider_senderid;
                break;

            case 'orange_cm':
                $this->orange_cm_application_id = $this->setting->sms_provider_app_id ?? '';
                $this->orange_cm_client_id = $this->sms_provider_username;
                $this->orange_cm_client_secret = $this->sms_provider_password;
                $this->orange_cm_sender_address = $this->sms_provider_senderid;
                $this->orange_cm_msp_username = $this->setting->sms_msp_username ?? '';
                $this->orange_cm_msp_password = $this->setting->sms_msp_password ?? '';
                break;

            default:
                // For unknown providers, use generic fields
                $this->nexah_username = $this->sms_provider_username;
                $this->nexah_password = $this->sms_provider_password;
                $this->nexah_senderid = $this->sms_provider_senderid;
                break;
        }
    }

    public function updatedSmsProvider()
    {
        // When SMS provider changes, reinitialize provider-specific properties
        $this->initializeProviderSpecificProperties();
    }

    public function updatedSnsRegion()
    {
        // When SNS region changes, update ses_region immediately
        $this->ses_region = $this->sns_region;
    }

    private function mapProviderSpecificFieldsToGeneric()
    {
        // Map provider-specific fields back to generic database fields based on selected provider
        switch ($this->sms_provider) {
            case 'nexah':
                $this->sms_provider_username = $this->nexah_username;
                $this->sms_provider_password = $this->nexah_password;
                $this->sms_provider_senderid = $this->nexah_senderid;
                break;

            case 'twilio':
                $this->sms_provider_username = $this->twilio_account_sid;
                $this->sms_provider_password = $this->twilio_auth_token;
                $this->sms_provider_senderid = $this->twilio_phone_number;
                break;

            case 'aws_sns':
                $this->sms_provider_username = $this->sns_access_key;
                $this->sms_provider_password = $this->sns_secret_key;
                $this->sms_provider_senderid = $this->sns_senderid;
                // Update the ses_region as well since SNS uses it
                $this->ses_region = $this->sns_region;
                break;

            case 'orange_cm':
                $this->setting->sms_provider_app_id = $this->orange_cm_application_id;
                $this->sms_provider_username = $this->orange_cm_client_id;
                $this->sms_provider_password = $this->orange_cm_client_secret;
                $this->sms_provider_senderid = $this->orange_cm_sender_address;
                $this->setting->sms_msp_username = $this->orange_cm_msp_username ?: null;
                $this->setting->sms_msp_password = $this->orange_cm_msp_password ?: null;
                break;

            default:
                // For unknown providers, fields should already be in generic properties
                break;
        }
    }

    public function saveSmsConfig()
    {
        // Map provider-specific fields back to generic database fields
        $this->mapProviderSpecificFieldsToGeneric();

        $setting = Setting::updateOrCreate(
            ['company_id'=> 1],
            [
                'company_id'=> 1,
                'sms_provider' => $this->sms_provider,
                'sms_provider_username' => $this->sms_provider_username,
                'sms_provider_password' => $this->sms_provider_password,
                'sms_provider_senderid' => $this->sms_provider_senderid,
                'sms_provider_app_id' => $this->sms_provider === 'orange_cm' ? ($this->setting->sms_provider_app_id ?? null) : null,
                'sms_msp_username' => $this->sms_provider === 'orange_cm' ? ($this->orange_cm_msp_username ?: null) : null,
                'sms_msp_password' => $this->sms_provider === 'orange_cm' ? ($this->orange_cm_msp_password ?: null) : null,
                'sms_content_en' => $this->sms_content_en,
                'sms_content_fr' => $this->sms_content_fr,
                'birthday_sms_message_en' => $this->birthday_sms_message_en,
                'birthday_sms_message_fr' => $this->birthday_sms_message_fr,
                // Save SES region for SNS provider
                'ses_region' => $this->ses_region,
              
            ]);

        if (!empty($setting)) {

            if (!empty($setting->sms_provider_username) && !empty($setting->sms_provider_password)) {

                $sms_client = match ($setting->sms_provider) {
                    'twilio' => new TwilioSMS($setting),
                    'nexah' =>  new Nexah($setting),
                    'aws_sns' => new AwsSnsSMS($setting),
                    'orange_cm' => new OrangeCameroonSMS($setting),
                    default => new Nexah($setting)
                };

                $response = match ($setting->sms_provider) {
                    'twilio' => ['responsecode' => 0],
                    'nexah' =>  $sms_client->getBalance(),
                    'aws_sns' => $sms_client->getBalance(),
                    'orange_cm' => $sms_client->getBalance(),
                    default => ['responsecode' => 0]
                };

                $credit = $response['responsecode'] === 1 ? ($response['credit'] ?? null) : 0;
                $this->sms_balance = $credit;

                $setting->update([
                    'sms_balance' => $response['responsecode'] === 1 ? $credit : 0,
                ]);
                
            }
        }

        $this->showToast(__('settings.setting_for_sms_successfully_added'), 'success');

    }
    public function saveSmtpConfig()
    {
        $setting = Setting::updateOrCreate(
            ['company_id' => 1],
            [
                'company_id' => 1,
                'smtp_provider' => $this->smtp_provider ?: 'smtp',
                'mailgun_domain' => $this->mailgun_domain,
                'mailgun_secret' => $this->mailgun_secret,
                'mailgun_endpoint' => $this->mailgun_endpoint,
                'mailgun_scheme' => $this->mailgun_scheme,
                'ses_key' => $this->ses_key,
                'ses_secret' => $this->ses_secret,
                'ses_region' => $this->ses_region,
                'postmark_token' => $this->postmark_token,
                'sendmail_path' => $this->sendmail_path,
                'mailpit_host' => $this->mailpit_host,
                'mailpit_port' => $this->mailpit_port,
                'log_channel' => $this->log_channel,
                'mailchimp_api_key' => $this->mailchimp_api_key,
                'smtp_host' => $this->smtp_host,
                'smtp_port' => $this->smtp_port,
                'smtp_username' => $this->smtp_username,
                'smtp_password' => $this->smtp_password,
                'smtp_encryption' => $this->smtp_encryption,
                'from_email' => $this->from_email,
                'from_name' => $this->from_name,
                'replyTo_email' => $this->replyTo_email,
                'replyTo_name' => $this->replyTo_name,
                'email_content_en' => $this->email_content_en,
                'email_content_fr' => $this->email_content_fr,
                'email_subject_fr' => $this->email_subject_fr,
                'email_subject_en' => $this->email_subject_en,
                'welcome_email_content_en' => $this->welcome_email_content_en,
                'welcome_email_content_fr' => $this->welcome_email_content_fr,
                'welcome_email_subject_fr' => $this->welcome_email_subject_fr,
                'welcome_email_subject_en' => $this->welcome_email_subject_en,
            ]
        );

        setSavedSmtpCredentials();

        $this->showToast(__('settings.setting_for_smtp_successfully_added'), 'success');
    }

    public function sendTestEmail()
    {
        $setting = Setting::first();

        $this->validate(['test_email_address'=>'required|email']);

        if (empty($setting) || (empty($setting->smtp_host) && empty($setting->smtp_port) && $setting->smtp_provider !== 'mailchimp')) {
            $this->showToast(__('settings.setting_for_smtp_required'), 'danger');
            return;
        }

        try {
            setSavedSmtpCredentials();

            Mail::to($this->test_email_address)->send(new TestEmail($this->test_email_message));

            $this->showToast(__('settings.test_email_sent_successfully'), 'success');
        } catch (\Symfony\Component\Mailer\Exception\TransportException $e) {
            $this->showToast(__('settings.test_email_failed') . ': ' . $e->getMessage(), 'danger');
        } catch (\Throwable $e) {
            $this->showToast(__('settings.test_email_failed') . ': ' . $e->getMessage(), 'danger');
        }
    }

    public function sendTestSms()
    {
        $setting = Setting::first();

        $this->validate(['test_phone_number'=>'required|string']);

        if (empty($setting) || (empty($setting->sms_provider_username) && empty($setting->sms_provider_password))) {
            $this->showToast(__('settings.setting_for_sms_required'), 'danger');
            return;
        }

        try {
            $sms_client = match ($setting->sms_provider) {
                'twilio' => new TwilioSMS($setting),
                'nexah' =>  new Nexah($setting),
                'aws_sns' => new AwsSnsSMS($setting),
                'orange_cm' => new OrangeCameroonSMS($setting),
                default => new Nexah($setting)
            };

            $response = $sms_client->sendSMS([
                'sms' =>  $this->test_sms_message,
                'mobiles' => $this->test_phone_number,
            ]);

            if ($response['responsecode'] === 1) {
                $toast = $setting->sms_provider === 'orange_cm'
                    ? __('settings.test_sms_queued_orange_cm')
                    : __('settings.test_sms_sent_successfully');
                $this->showToast($toast, 'success');
            } else {
                $errorMessage = $response['error'] ?? __('settings.test_sms_failed');
                $this->showToast($errorMessage, 'danger');
            }
        } catch (\Throwable $e) {
            $this->showToast(__('settings.test_sms_failed') . ': ' . $e->getMessage(), 'danger');
        }
    }

    public function saveFeatureConfiguration()
    {
        $setting = Setting::updateOrCreate(
            ['company_id' => 1],
            [
                'company_id' => 1,
                'inactivity_deactivation_enabled' => $this->inactivity_deactivation_enabled,
                'inactivity_months_threshold' => $this->inactivity_months_threshold,
                'deactivation_check_time' => $this->deactivation_check_time,
            ]
        );

        if ($setting) {
            $this->showToast(__('common.saved_successfully'), 'success');
        }
    }

    public function saveSftpConfiguration()
    {
        $setting = Setting::updateOrCreate(
            ['company_id' => 1],
            [
                'company_id' => 1,
                'sftp_sync_enabled' => $this->sftp_sync_enabled,
                'sftp_push_username' => $this->sftp_push_username,
                'sftp_push_password' => $this->sftp_push_password,
                'sftp_push_path' => $this->sftp_push_path,
                'sftp_sync_frequency' => $this->sftp_sync_frequency,
                'sftp_push_scan_frequency' => $this->sftp_push_scan_frequency,
                'sftp_push_scan_days' => implode(',', $this->sftp_push_scan_days ?? []),
                'sftp_push_scan_time' => $this->sftp_push_scan_time,
                'sftp_push_archive_frequency' => $this->sftp_push_archive_frequency ?: null,
                'sftp_push_archive_days' => implode(',', $this->sftp_push_archive_days ?? []),
                'sftp_push_archive_time' => $this->sftp_push_archive_time,
                'sftp_push_archive_min_age_minutes' => max(0, (int) $this->sftp_push_archive_min_age_minutes),
                'sftp_push_archive_move_processed' => (bool) $this->sftp_push_archive_move_processed,
                'sftp_push_archive_move_rejected' => (bool) $this->sftp_push_archive_move_rejected,
                'sftp_push_archive_move_failed' => (bool) $this->sftp_push_archive_move_failed,
                'sftp_push_archive_require_successful_process' => (bool) $this->sftp_push_archive_require_successful_process,
                'sftp_matching_strategies' => $this->sftp_matching_strategies,
                'sftp_auto_match_enabled' => $this->sftp_auto_match_enabled,
                'sftp_auto_match_threshold' => (int) $this->sftp_auto_match_threshold,
                'sftp_auto_match_min_strategy' => $this->sftp_auto_match_min_strategy,
                'sftp_auto_match_notification_email' => $this->normalizeEmails($this->sftp_auto_match_notification_email),
                'sftp_match_created_notification_enabled' => (bool) $this->sftp_match_created_notification_enabled,
                'sftp_match_created_notification_email' => $this->normalizeEmails($this->sftp_match_created_notification_email),
                'sftp_os_username' => $this->sftp_os_username ?: null,
                'sftp_os_password' => $this->sftp_os_password ?: null,
                'sftp_server_host' => $this->sftp_server_host ?: null,
                'sftp_server_port' => (int) $this->sftp_server_port ?: 22,
            ]
        );

        if ($setting) {
            \App\Services\FeatureConfigurationService::clearCache();
            // Update checksum for push path changes
            $this->checkSftpConnectionStatus();
            $this->showToast(__('common.saved_successfully'), 'success');
        }
    }

    /**
     * Show the server setup script using existing credentials (if any).
     * Only generates new credentials when none exist yet.
     */
    public function generateSftpOsCredentials()
    {
        $this->buildSftpOsDisplay(forceNew: false);
    }

    /**
     * Force-generate a brand-new username + password and rebuild the script.
     * Called explicitly by the admin when they want to rotate credentials.
     */
    public function regenerateSftpOsCredentials()
    {
        $this->buildSftpOsDisplay(forceNew: true);
    }

    /**
     * Core logic: build $sftp_os_generated_display.
     * When $forceNew is false and credentials already exist, reuse them.
     */
    private function buildSftpOsDisplay(bool $forceNew): void
    {
        $existingUsername = $this->sftp_os_username;
        $existingPassword = $this->sftp_os_password;
        $hasExisting      = $existingUsername && $existingPassword;

        if ($forceNew || ! $hasExisting) {
            $username = 'sftp_' . \Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(8));
            $password = \Illuminate\Support\Str::random(20);
        } else {
            $username = $existingUsername;
            $password = $existingPassword;
        }

        // $absPath = the real Laravel storage directory (app reads/scans here)
        // $chrootPath = /var/sftp/... with root:root ancestors (sshd chroot requirement)
        // incoming/ inside the chroot is bind-mounted from $absPath/incoming so files
        // dropped by the SFTP client appear directly in the Laravel storage path.
        $absPath    = base_path($this->sftp_push_path ?? 'storage/app/sftp-push');
        $chrootPath = '/var/sftp/ciblerh-push';
        $host       = $this->sftp_server_host ?: request()->getHost();
        $port       = (int) ($this->sftp_server_port ?: 22);

        $this->sftp_os_username = $username;
        $this->sftp_os_password = $password;
        $this->sftp_server_host = $host;
        $this->sftp_server_port = $port;

        // Persist immediately so the credentials survive a page reload
        Setting::updateOrCreate(
            ['company_id' => 1],
            [
                'sftp_os_username' => $username,
                'sftp_os_password' => $password,
                'sftp_server_host' => $host,
                'sftp_server_port' => $port,
            ]
        );

        $incomingPath       = $absPath . '/incoming';
        $chrootIncomingPath = $chrootPath . '/incoming';

        $this->sftp_os_generated_display = [
            'username' => $username,
            'password' => $password,
            'host'     => $host,
            'port'     => $port,
            'path'     => '/incoming',
            'script'   => implode("\n", [
                "# Run as root on the server",
                "",
                "# 1. Create OS user (SFTP-only, no shell)",
                "useradd -M -s /usr/sbin/nologin {$username}",
                "echo '{$username}:{$password}' | chpasswd",
                "",
                "# 2. Chroot jail root — must sit under /var/sftp so all ancestors are root:root.",
                "#    sshd silently kills sessions if any ancestor is group-writable.",
                "mkdir -p {$chrootPath}",
                "chown root:root {$chrootPath}",
                "chmod 755 {$chrootPath}",
                "",
                "# 3. incoming/ inside the chroot — SFTP user writes here",
                "mkdir -p {$chrootIncomingPath}",
                "chown {$username}:laravel {$chrootIncomingPath}",
                "chmod 2775 {$chrootIncomingPath}",
                "",
                "# 4. Ensure the real Laravel incoming/ dir exists",
                "mkdir -p {$incomingPath}",
                "chown {$username}:laravel {$incomingPath}",
                "chmod 2775 {$incomingPath}",
                "",
                "# 4a. Add SFTP user to laravel group so they can write to laravel-owned dirs",
                "usermod -aG laravel {$username}",
                "",
                "# 5. Bind-mount the real incoming/ into the chroot so the app sees files immediately",
                "mount --bind {$incomingPath} {$chrootIncomingPath}",
                "",
                "# 6. Persist the bind mount across reboots",
                "grep -qF '{$chrootIncomingPath}' /etc/fstab || echo '{$incomingPath} {$chrootIncomingPath} none bind 0 0' >> /etc/fstab",
                "",
                "# 7. Ensure Subsystem uses internal-sftp (required for ChrootDirectory)",
                "sed -i 's|^Subsystem.*sftp.*|Subsystem sftp internal-sftp|' /etc/ssh/sshd_config",
                "",
                "# 8. Add Match User block (idempotent — removes any existing block first)",
                "sed -i '/^Match User {$username}/,/^    X11Forwarding no/d' /etc/ssh/sshd_config",
                "cat >> /etc/ssh/sshd_config << 'SSHEOF'",
                "Match User {$username}",
                "    ChrootDirectory {$chrootPath}",
                "    ForceCommand internal-sftp",
                "    PasswordAuthentication yes",
                "    AllowTcpForwarding no",
                "    X11Forwarding no",
                "SSHEOF",
                "",
                "# 9. Validate config then reload",
                "sshd -t && systemctl reload sshd",
                "",
                "# Client remote path to set in FileZilla/WinSCP: /incoming",
            ]),
        ];

        $toast = $forceNew
            ? 'New SFTP OS credentials generated. Update the server and your SFTP client.'
            : ($hasExisting ? 'Server setup script loaded with existing credentials.' : 'SFTP OS credentials generated. Copy and run the server script.');

        $this->showToast($toast, 'success');
    }

    /**
     * Normalise a potentially messy (comma/newline separated) email string.
     * Returns a clean comma-separated string, or null when empty.
     */
    private function normalizeEmails(?string $value): ?string
    {
        if (empty(trim((string) $value))) {
            return null;
        }

        $emails = preg_split('/[\s,]+/', (string) $value, -1, PREG_SPLIT_NO_EMPTY);
        $emails = array_values(array_filter(array_map('trim', $emails)));

        return $emails ? implode(', ', $emails) : null;
    }

    /**
     * Check if SFTP push is already configured
     */
    private function checkSftpConnectionStatus()
    {
        // Connected if multi-user table has at least one active user, OR legacy single-user is set
        if (\App\Models\SftpUser::where('is_active', true)->exists()) {
            $this->sftp_connection_status = true;
            return;
        }

        if (!empty($this->sftp_push_username) && !empty($this->sftp_push_password) && !empty($this->sftp_push_path)) {
            $this->sftp_connection_status = true;
        } else {
            $this->sftp_connection_status = false;
        }
    }

    // ─── Multi-user SFTP management ───────────────────────────────────────────

    /**
     * Load all SFTP users from DB into the $sftpUsers array.
     * Each entry includes pre-generated script data so the blade can render everything server-side
     * and Alpine.js can toggle visibility purely client-side with no further round-trips.
     */
    private function loadSftpUsers(): void
    {
        $this->sftpUsers = \App\Models\SftpUser::orderBy('id')
            ->get()
            ->map(fn (\App\Models\SftpUser $u) => array_merge([
                'id'             => $u->id,
                'username'       => $u->username,
                'password'       => $u->password,
                'home_directory' => $u->home_directory,
                'is_active'      => $u->is_active,
            ], $this->generateScriptData($u)))
            ->all();
    }

    /**
     * Generate the OS chroot-jail setup script data for a given SftpUser.
     * Returns ['host', 'port', 'script'] for embedding in the $sftpUsers array.
     */
    private function generateScriptData(\App\Models\SftpUser $user): array
    {
        $host               = $this->sftp_server_host ?: request()->getHost();
        $port               = (int) ($this->sftp_server_port ?: 22);
        $absPath            = $user->absoluteHomePath();
        $chrootPath         = '/var/sftp/' . $user->username;
        $incomingPath       = $absPath . '/incoming';
        $processedPath      = $absPath . '/processed';
        $failedPath         = $absPath . '/failed';
        $chrootIncomingPath = $chrootPath . '/incoming';
        $chrootProcessedPath = $chrootPath . '/processed';
        $chrootFailedPath    = $chrootPath . '/failed';
        $username           = $user->username;
        $password           = $user->password;

        $script = implode("\n", [
            "# Run as root on the server (for user: {$username})",
            "",
            "# 1. Create OS user (SFTP-only, no shell)",
            "useradd -M -s /usr/sbin/nologin {$username}",
            "echo '{$username}:{$password}' | chpasswd",
            "",
            "# 2. Chroot jail root — must sit under /var/sftp so all ancestors are root:root.",
            "#    sshd silently kills sessions if any ancestor is group-writable.",
            "mkdir -p {$chrootPath}",
            "chown root:root {$chrootPath}",
            "chmod 755 {$chrootPath}",
            "",
            "# 3. incoming/, processed/ and failed/ inside the chroot",
            "#    - incoming/: SFTP user uploads here",
            "#    - processed/: app moves successfully processed files here",
            "#    - failed/: app moves permanently failed files here",
            "mkdir -p {$chrootIncomingPath}",
            "mkdir -p {$chrootProcessedPath}",
            "mkdir -p {$chrootFailedPath}",
            "chown {$username}:laravel {$chrootIncomingPath}",
            "chmod 2775 {$chrootIncomingPath}",
            "chown {$username}:laravel {$chrootProcessedPath}",
            "chmod 2775 {$chrootProcessedPath}",
            "chown {$username}:laravel {$chrootFailedPath}",
            "chmod 2775 {$chrootFailedPath}",
            "",
            "# 4. Ensure the real Laravel incoming/, processed/ and failed/ dirs exist",
            "mkdir -p {$incomingPath}",
            "mkdir -p {$processedPath}",
            "mkdir -p {$failedPath}",
            "chown {$username}:laravel {$incomingPath}",
            "chmod 2775 {$incomingPath}",
            "chown {$username}:laravel {$processedPath}",
            "chmod 2775 {$processedPath}",
            "chown {$username}:laravel {$failedPath}",
            "chmod 2775 {$failedPath}",
            "",
            "# 4a. Add SFTP user to laravel group so they can write to laravel-owned dirs",
            "usermod -aG laravel {$username}",
            "",
            "# 5. Bind-mount the real Laravel folders into the chroot so the SFTP user can",
            "#    upload to incoming/ and see processed/ / failed/ lifecycle folders",
            "mount --bind {$incomingPath} {$chrootIncomingPath}",
            "mount --bind {$processedPath} {$chrootProcessedPath}",
            "mount --bind {$failedPath} {$chrootFailedPath}",
            "",
            "# 6. Persist the bind mount across reboots",
            "grep -qF '{$chrootIncomingPath}' /etc/fstab || echo '{$incomingPath} {$chrootIncomingPath} none bind 0 0' >> /etc/fstab",
            "grep -qF '{$chrootProcessedPath}' /etc/fstab || echo '{$processedPath} {$chrootProcessedPath} none bind 0 0' >> /etc/fstab",
            "grep -qF '{$chrootFailedPath}' /etc/fstab || echo '{$failedPath} {$chrootFailedPath} none bind 0 0' >> /etc/fstab",
            "",
            "# 7. Ensure Subsystem uses internal-sftp (required for ChrootDirectory)",
            "sed -i 's|^Subsystem.*sftp.*|Subsystem sftp internal-sftp|' /etc/ssh/sshd_config",
            "",
            "# 8. Add Match User block (idempotent — removes any existing block first)",
            "sed -i '/^Match User {$username}/,/^    X11Forwarding no/d' /etc/ssh/sshd_config",
            "cat >> /etc/ssh/sshd_config << 'SSHEOF'",
            "Match User {$username}",
            "    ChrootDirectory {$chrootPath}",
            "    ForceCommand internal-sftp",
            "    PasswordAuthentication yes",
            "    AllowTcpForwarding no",
            "    X11Forwarding no",
            "SSHEOF",
            "",
            "# 9. Validate config then reload",
            "sshd -t && systemctl reload sshd",
            "",
            "# Client remote paths inside the chroot:",
            "#   /incoming  → upload drop folder",
            "#   /processed → successfully processed files",
            "#   /failed    → permanently failed files",
        ]);

        return [
            'host'   => $host,
            'port'   => $port,
            'script' => $script,
        ];
    }

    /**
     * Generate a new SFTP user with random credentials, save to DB, refresh list.
     * Maximum 4 active users enforced.
     */
    public function addSftpUser(): void
    {
        $activeCount = \App\Models\SftpUser::where('is_active', true)->count();
        if ($activeCount >= 4) {
            $this->showToast(__('settings.sftp_users_max_reached'), 'warning');
            return;
        }

        $username = 'sftp_' . Str::lower(Str::random(8));
        $password = Str::random(20);
        $index    = $activeCount + 1;
        $homeDir  = 'storage/app/sftp-push/user' . $index;

        $user = \App\Models\SftpUser::create([
            'username'       => $username,
            'password'       => $password,
            'home_directory' => $homeDir,
            'is_active'      => true,
        ]);

        // Ensure the standard SFTP subdirectories exist for this user.
        @mkdir(base_path($homeDir) . '/incoming', 0775, true);
        @mkdir(base_path($homeDir) . '/processed', 0775, true);
        @mkdir(base_path($homeDir) . '/failed', 0775, true);

        $this->loadSftpUsers();
        $this->checkSftpConnectionStatus();
        $this->showToast(__('settings.sftp_user_added'), 'success');
    }

    /**
     * Remove an SFTP user (hard delete).
     */
    public function removeSftpUser(int $id): void
    {
        \App\Models\SftpUser::where('id', $id)->delete();
        $this->loadSftpUsers();
        $this->checkSftpConnectionStatus();
        $this->showToast(__('settings.sftp_user_removed'), 'success');
    }

    /**
     * Stage an SFTP user for deletion and open the confirmation modal.
     */
    public function confirmRemoveSftpUser(int $id): void
    {
        $this->sftpUserToDelete = $id;
    }

    /**
     * Called by the shared DeleteModal confirm button.
     * Delegates to removeSftpUser when an SFTP user delete is pending.
     */
    public function delete(): void
    {
        if ($this->sftpUserToDelete) {
            $this->removeSftpUser($this->sftpUserToDelete);
            $this->sftpUserToDelete = null;
        }
        $this->dispatch('close-modal', id: 'DeleteModal');
    }



    /**
     * Generate SFTP push credentials (username + password) for receiving payslips
     * The credentials are displayed once and stored in settings
     */
    public function generateSftpPushCredentials()
    {
        $username = 'push_' . Str::lower(Str::random(8));
        $password = Str::random(24);

        $this->sftp_push_username = $username;
        $this->sftp_push_password = $password;
        $this->sftp_generated_username_display = $username;
        $this->sftp_generated_password_display = $password;

        $this->showToast(__('settings.sftp_credentials_generated'), 'success');
    }

    /**
     * Save generated push credentials to settings
     */
    public function saveSftpPushCredentials()
    {
        if (empty($this->sftp_push_username) || empty($this->sftp_push_password)) {
            $this->addError('sftp_push_credentials', __('settings.push_credentials_required'));
            return;
        }

        // Auto-create the push directory if it doesn't exist
        $pushPath = base_path($this->sftp_push_path);
        if (!file_exists($pushPath)) {
            @mkdir($pushPath, 0755, true);
        }

        // Create 'incoming' subdirectory if it doesn't exist
        $incomingDir = $pushPath . '/incoming';
        if (!file_exists($incomingDir)) {
            @mkdir($incomingDir, 0755, true);
        }

        // Create 'processed' subdirectory if it doesn't exist
        $processedDir = $pushPath . '/processed';
        if (!file_exists($processedDir)) {
            @mkdir($processedDir, 0755, true);
        }

        // Create 'failed' subdirectory if it doesn't exist
        $failedDir = $pushPath . '/failed';
        if (!file_exists($failedDir)) {
            @mkdir($failedDir, 0755, true);
        }

        Setting::updateOrCreate(
            [],
            [
                'sftp_push_username' => $this->sftp_push_username,
                'sftp_push_password' => $this->sftp_push_password,
                'sftp_push_path' => $this->sftp_push_path,
                'sftp_credentials_generated_at' => now(),
            ]
        );

        $this->showToast(__('settings.push_credentials_saved'), 'success');
        $this->sftp_generated_username_display = null;
        $this->sftp_generated_password_display = null;
    }

    /**
     * Test SFTP push endpoint and path accessibility
     */
    public function testSftpPushConfiguration()
    {
        try {
            $filesystemService = new \App\Services\FilesystemPayslipService();
            $result = $filesystemService->testPushPath();

            if ($result['success']) {
                $this->sftp_connection_status = true;
                $this->test_sftp_message = $result['message'] . '. Files found: ' . ($result['file_count'] ?? 0);
                $this->showToast(__('settings.test_connection_success'), 'success');
            } else {
                $this->sftp_connection_status = false;
                $this->test_sftp_message = $result['message'];
                $this->showToast($result['message'], 'warning');
            }
        } catch (\Exception $e) {
            $this->sftp_connection_status = false;
            $this->test_sftp_message = $e->getMessage();
            $this->showToast(__('settings.test_connection_failed') . ': ' . $e->getMessage(), 'danger');
        }
    }

    /**
     * Manually trigger an immediate scan of all configured SFTP push incoming folders.
     * Uses the same command as the scheduler for consistent behavior.
     */
    public function pullSftpFilesNow(): void
    {
        if (!$this->sftp_sync_enabled) {
            $this->showToast(__('settings.sftp_scan_now_disabled'), 'warning');
            return;
        }

        try {
            Artisan::call('sftp:scan-push-folder', ['--sync' => true]);
            $output = trim(Artisan::output());

            if (!empty($output)) {
                $this->test_sftp_message = Str::limit($output, 800);
            }

            $this->showToast(__('settings.sftp_scan_now_success'), 'success');
        } catch (\Throwable $e) {
            $this->showToast(__('settings.sftp_scan_now_failed') . ': ' . $e->getMessage(), 'danger');
        }
    }

    private function resolveSftpSetting(): ?Setting
    {
        $primary = Setting::query()
            ->where('company_id', 1)
            ->latest('id')
            ->first();

        if ($primary) {
            return $primary;
        }

        $configured = Setting::query()
            ->where(function ($query) {
                $query->whereRaw("TRIM(COALESCE(sftp_push_path, '')) <> ''")
                    ->orWhereRaw("TRIM(COALESCE(sftp_push_username, '')) <> ''");
            })
            ->latest('id')
            ->first();

        if ($configured) {
            return $configured;
        }

        return Setting::query()->latest('id')->first();
    }

    /**
     * Copy push credentials to clipboard (JSON format for integration documentation)
     */
    public function copyPushCredentialsToClipboard()
    {
        $credentials = [
            'http_endpoint' => route('sftp.upload'),
            'username' => $this->sftp_push_username,
            'password' => $this->sftp_push_password,
            'auth_type' => 'http_basic',
            'example_curl' => 'curl -F "file=@payslip.pdf" -u ' . $this->sftp_push_username . ':' . $this->sftp_push_password . ' ' . route('sftp.upload'),
        ];

        $this->dispatch('copy-to-clipboard', json_encode($credentials, JSON_PRETTY_PRINT));
        $this->showToast(__('settings.credentials_copied'), 'success');
    }

    /**
     * Regenerate push credentials (creates new username/password)
     */
    public function regenerateSftpPushCredentials()
    {
        $this->generateSftpPushCredentials();
    }

    /**
     * Clear the one-time password display after the user has copied the credentials.
     */
    public function clearSftpGeneratedPasswordDisplay()
    {
        $this->sftp_generated_username_display = null;
        $this->sftp_generated_password_display = null;
    }

    /**
     * Save SFTP configuration to settings
     */
    public function save()
    {
        if (!Gate::allows('manage-settings')) {
            return abort(401);
        }

        Setting::updateOrCreate(
            [],
            [
                'sftp_sync_enabled' => $this->sftp_sync_enabled,
                'sftp_push_path' => $this->sftp_push_path,
                'sftp_sync_frequency' => $this->sftp_sync_frequency,
                'sftp_push_scan_frequency' => $this->sftp_push_scan_frequency,
                'sftp_push_scan_days' => implode(',', $this->sftp_push_scan_days ?? []),
                'sftp_push_scan_time' => $this->sftp_push_scan_time,
                'sftp_push_archive_frequency' => $this->sftp_push_archive_frequency ?: null,
                'sftp_push_archive_days' => implode(',', $this->sftp_push_archive_days ?? []),
                'sftp_push_archive_time' => $this->sftp_push_archive_time,
                'sftp_push_archive_min_age_minutes' => max(0, (int) $this->sftp_push_archive_min_age_minutes),
                'sftp_push_archive_move_processed' => (bool) $this->sftp_push_archive_move_processed,
                'sftp_push_archive_move_rejected' => (bool) $this->sftp_push_archive_move_rejected,
                'sftp_push_archive_move_failed' => (bool) $this->sftp_push_archive_move_failed,
                'sftp_push_archive_require_successful_process' => (bool) $this->sftp_push_archive_require_successful_process,
                'sftp_matching_strategies' => $this->sftp_matching_strategies,
            ]
        );

        $this->showToast(__('settings.configuration_saved'), 'success');
    }

    public function render()
    {
        return view('livewire.portal.settings.index')->layout('components.layouts.dashboard');
    }
}
