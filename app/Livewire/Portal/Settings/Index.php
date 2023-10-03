<?php

namespace App\Livewire\Portal\Settings;

use App\Models\Setting;
use App\Services\Nexah;
use Livewire\Component;
use Illuminate\Support\Facades\Mail;
use App\Livewire\Traits\WithDataTable;
use App\Mail\TestEmail;
use Illuminate\Support\Facades\Config;

class Index extends Component
{
    use WithDataTable;

    public $setting, $sms_provider_username, $sms_provider_password, $sms_provider_senderid;

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

    public function mount() {

        $this->setting = Setting::first();

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
    }


    public function saveSmsConfig()
    {
        $setting = Setting::updateOrCreate(
            ['company_id'=> 1],
            [
                'company_id'=> 1,
                'sms_provider_username' => $this->sms_provider_username,
                'sms_provider_password' => $this->sms_provider_password,
                'sms_provider_senderid' => $this->sms_provider_senderid,
            ]);

        $this->closeModalAndFlashMessage(__('Setting for SMS successfully added!'),'');
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
            ]
        );

        setSavedSmtpCredentials();

        $this->closeModalAndFlashMessage(__('Setting for SMTP successfully added!'),'');
    }

     public function sendTestEmail()
     {
        $setting = Setting::first();

        $this->validate(['test_email_address'=>'required|email']);

        if(empty($setting->smtp_host) && empty($setting->smtp_port))
        {
            $this->closeModalAndFlashMessage(__('Setting for SMTP required!'), '');
        }

        setSavedSmtpCredentials();

        Mail::to($this->test_email_address)->send(new TestEmail);

        $this->closeModalAndFlashMessage(__('TestEmail sent successfully!'), '');
     }

    public function render()
    {
        $setting = 

        $sms_balance = 0;

        if (!empty($this->setting)) {

            if (!is_null($this->setting->sms_provider_username) && !is_null($this->setting->sms_provider_password)) {
                $sms_client = new Nexah($this->setting);

                $response = $sms_client->getBalance();

                $sms_balance = $response['responsecode'] === 1 ? $response['credit'] : 0;
            }
        }

        return view('livewire.portal.settings.index', compact('setting', 'sms_balance'))->layout('components.layouts.dashboard');
    }
}
