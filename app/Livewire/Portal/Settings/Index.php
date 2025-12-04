<?php

namespace App\Livewire\Portal\Settings;

use App\Mail\TestEmail;
use App\Models\Setting;
use App\Services\Nexah;
use Livewire\Component;
use App\Services\TwilioSMS;
use App\Services\AwsSnsSMS;
use Illuminate\Support\Facades\Mail;
use App\Livewire\Traits\WithDataTable;
use Illuminate\Support\Facades\Config;

class Index extends Component
{
    use WithDataTable;

    public $setting, $sms_provider, $sms_provider_username, $sms_provider_password, $sms_provider_senderid;

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
    public $from_email;
    public $from_name;
    public $replyTo_email;
    public $replyTo_name;
    public $test_email_address;
    public $test_email_message;
    public $test_phone_number;
    public $test_sms_message;
    public $sms_balance = 0;
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

    public function mount() {

        $this->setting = Setting::first();

        $this->sms_provider= !empty($this->setting) ? $this->setting->sms_provider: '';
        $this->sms_provider_username = !empty($this->setting) ? $this->setting->sms_provider_username : '';
        $this->sms_provider_password = !empty($this->setting) ? $this->setting->sms_provider_password :'';
        $this->sms_provider_senderid = !empty($this->setting) ? $this->setting->sms_provider_senderid :'';
        $this->smtp_host = !empty($this->setting) ? $this->setting->smtp_host :'';
        $this->smtp_port = !empty($this->setting) ? $this->setting->smtp_port :'';
        $this->smtp_username = !empty($this->setting) ? $this->setting->smtp_username :'';
        $this->smtp_password = !empty($this->setting) ? $this->setting->smtp_password :'';
        $this->smtp_encryption = !empty($this->setting) ? $this->setting->smtp_encryption :'';
        $this->from_email = !empty($this->setting) ? $this->setting->from_email :'';
        $this->from_name = !empty($this->setting) ? $this->setting->from_name :'';
        $this->replyTo_email = !empty($this->setting) ? $this->setting->replyTo_email :'';
        $this->replyTo_name = !empty($this->setting) ? $this->setting->replyTo_name :'';
        $this->sms_balance = !empty($this->setting) ? $this->setting->sms_balance :'';

        $this->sms_content_en = !empty($this->setting) ? (!empty($this->setting->sms_content_en) ? $this->setting->sms_content_en  : "Mr/Mrs :name:, your pay slip for the month of :month:-:year: has been sent to your mailbox. Please use the following password: :pdf_password: to view it.") :'';
        $this->sms_content_fr = !empty($this->setting) ? (!empty($this->setting->sms_content_fr) ? $this->setting->sms_content_fr : "M./Mme :name:, votre fiche de paie du mois de :month:-:year: a été envoyée dans votre boîte mail. Merci d'utiliser le mot de passe suivant : :pdf_password: pour la consulter."):'';
        $this->email_subject_en = !empty($this->setting) ? (!empty($this->setting->email_subject_en) ? $this->setting->email_subject_en  : "Your :month: :year: payslip.") :'';
        $this->email_subject_fr = !empty($this->setting) ? (!empty($this->setting->email_subject_fr) ? $this->setting->email_subject_fr : "Votre fiche de salaire :month: :year:."):'';
        $this->email_content_en = !empty($this->setting) ? (!empty($this->setting->email_content_en) ? $this->setting->email_content_en : "<h2>Dear :name:,</h2> <p>Please find your pay slip attached,</p> <p>How to open your pay slip:</p> <p>Download the PDF document attached to the email. You will be asked for your password</p><p>Enter the password received by SMS</p> <p>In case of difficulty, please call us or write to us using the contact details below:</p> <p>Call and text: :support_number:</p> <p>Mail: :mail_address:</p>") : '';
        $this->email_content_fr = !empty($this->setting) ? (!empty($this->setting->email_content_fr) ? $this->setting->email_content_fr : "<h2>Cher :name:,</h2> <p>Veuillez trouver votre fiche de paie en pièce jointe,</p> <p>Comment ouvrir votre fiche de paie :</p> <p>Téléchargez le document PDF joint au e-mail. Votre mot de passe vous sera demandé</p> <p>Saisissez le mot de passe reçu par SMS</p> <p>En cas de difficulté, merci de nous appeler ou de nous écrire aux coordonnées ci-dessous :</p> <p>Appel et SMS : support_number :</p> <p>Mail : mail_address :</p>") :'';

        $this->welcome_email_subject_en = !empty($this->setting) ? (!empty($this->setting->welcome_email_subject_en) ? $this->setting->welcome_email_subject_en : "EmploiServ - Login Credentials") :'';
        $this->welcome_email_subject_fr = !empty($this->setting) ? (!empty($this->setting->welcome_email_subject_fr) ? $this->setting->welcome_email_subject_fr :  "EmploiServ - Identifiants de connexion") :'';
        $this->welcome_email_content_en = !empty($this->setting) ? (!empty($this->setting->welcome_email_content_en) ? $this->setting->welcome_email_content_en : "<h3>Dear :name:,</h3> <p>Your account has been created and you can now login into the employee portal at, :site_url: your credentials are </p> <strong>Username :username:</strong> <br><strong>Password :password:</strong><p></p>  <p>In case of any difficulties, Contact your support via </p> <p>Call and text: :support_number:</p> <p>Mail: :mail_address:</p>") : '';
        $this->welcome_email_content_fr = !empty($this->setting) ? (!empty($this->setting->welcome_email_content_fr) ? $this->setting->welcome_email_content_fr : "<h2>Cher :name:,</h2> <p>Votre compte a été créé et vous pouvez désormais vous connecter au portail des employés sur,:site_url: vos identifiants sont </p> <strong>Nom d'utilisateur :username:</strong> <br><strong>Mot de passe :password:</strong><p></p> <p>En cas de difficultés, contactez votre support via </p> <p>Appel et SMS : :support_number:</p> <p>Mail : :mail_address:</p>") :'';

        $this->birthday_sms_message_en = !empty($this->setting) ? (!empty($this->setting->birthday_sms_message_en) ? $this->setting->birthday_sms_message_en : "Happy Birthday! :name:, Wishing you a fantastic day filled with joy and a year ahead full of success. Enjoy your special day!") :'';
        $this->birthday_sms_message_fr = !empty($this->setting) ? (!empty($this->setting->birthday_sms_message_fr) ? $this->setting->birthday_sms_message_fr : "Joyeux anniversaire! :name:, Je te souhaite une journée fantastique pleine de joie et une année à venir remplie de succès. Profite bien de ta journée spéciale!") :'';


    
    }

    public function saveSmsConfig()
    {
        $setting = Setting::updateOrCreate(
            ['company_id'=> 1],
            [
                'company_id'=> 1,
                'sms_provider' => $this->sms_provider,
                'sms_provider_username' => $this->sms_provider_username,
                'sms_provider_password' => $this->sms_provider_password,
                'sms_provider_senderid' => $this->sms_provider_senderid,
                'sms_content_en' => $this->sms_content_en,
                'sms_content_fr' => $this->sms_content_fr,
                'birthday_sms_message_en' => $this->birthday_sms_message_en,
                'birthday_sms_message_fr' => $this->birthday_sms_message_fr,
              
            ]);

        if (!empty($setting)) {

            if (!empty($setting->sms_provider_username) && !empty($setting->sms_provider_password)) {

                $sms_client = match ($setting->sms_provider) {
                    'twilio' => new TwilioSMS($setting),
                    'nexah' =>  new Nexah($setting),
                    'aws_sns' => new AwsSnsSMS($setting),
                    default => new Nexah($setting)
                };

                $response = match ($setting->sms_provider) {
                    'twilio' => ['responsecode' => 0],
                    'nexah' =>  $sms_client->getBalance(),
                    'aws_sns' => $sms_client->getBalance(),
                    default => ['responsecode' => 0]
                };

                $this->sms_balance = $response['responsecode'] === 1 ? $response['credit'] : 0;

                $setting->update([
                      'sms_balance' => $response['responsecode'] === 1 ? $response['credit'] : 0,
                ]);
                
            }
        }

        $this->closeModalAndFlashMessage(__('settings.setting_for_sms_successfully_added'),'');
        return $this->redirect(route('portal.settings.index'), navigate: true);

    }
    public function saveSmtpConfig()
    {
        $setting = Setting::updateOrCreate(
            ['company_id' => 1],
            [
                'company_id' => 1,
                'smtp_provider' => !empty($this->smtp_provider) ? $this->smtp_provider:'smtp',
                'mailgun_domain' => $this->mailgun_domain,
                'mailgun_secret' => $this->mailgun_secret,
                'mailgun_endpoint' => $this->mailgun_endpoint,
                'mailgun_scheme' => $this->mailgun_scheme,
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

        $this->closeModalAndFlashMessage(__('settings.setting_for_smtp_successfully_added'),'');

        return $this->redirect(route('portal.settings.index'), navigate: true);
    }

    public function sendTestEmail()
    {
        $setting = Setting::first();

        $this->validate(['test_email_address'=>'required|email']);

        if(empty($setting->smtp_host) && empty($setting->smtp_port))
        {
            $this->closeModalAndFlashMessage(__('settings.setting_for_smtp_required'), '');
        }

        setSavedSmtpCredentials();

        Mail::to($this->test_email_address)->send(new TestEmail($this->test_email_message));

        $this->closeModalAndFlashMessage(__('settings.test_email_sent_successfully'), '');

        return $this->redirect(route('portal.settings.index'), navigate: true);
    }

    public function sendTestSms()
    {
        $setting = Setting::first();

        $this->validate(['test_phone_number'=>'required|integer']);

        if (!empty($this->setting)) {
            if (empty($setting->sms_provider_username) && empty($setting->sms_provider_password)) {
                $this->closeModalAndFlashMessage(__('settings.setting_for_sms_required'), '');
            }

            $sms_client = match ($setting->sms_provider) {
                'twilio' => new TwilioSMS($setting),
                'nexah' =>  new Nexah($setting),
                'aws_sns' => new AwsSnsSMS($setting),
                default => new Nexah($setting)
            };

            $response = $sms_client->sendSMS([
                'sms' =>  $this->test_sms_message,
                'mobiles' => $this->test_phone_number,
            ]);

            if ($response['responsecode'] === 1) {
                $this->closeModalAndFlashMessage(__('settings.test_sms_sent_successfully'), '');
            } else {
                $this->closeModalAndFlashMessage(__('settings.test_sms_failed'), '');
            }
        }

        return $this->redirect(route('portal.settings.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.portal.settings.index')->layout('components.layouts.dashboard');
    }
}
