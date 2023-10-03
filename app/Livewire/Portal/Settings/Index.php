<?php

namespace App\Livewire\Portal\Settings;

use App\Models\Setting;
use App\Services\Nexah;
use Livewire\Component;
use App\Livewire\Traits\WithDataTable;

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

    public function mount() {
        $this->setting = Setting::first();

        $this->sms_provider_username = !empty($this->setting) ? $this->setting->sms_provider_username : '';
        $this->sms_provider_password = !empty($this->setting) ? $this->setting->sms_provider_password :'';
        $this->sms_provider_senderid = !empty($this->setting) ? $this->setting->sms_provider_senderid :'';
    }


    public function saveSmsConfig()
    {
        $setting = Setting::updateOrCreate(
            [''],
            [
                'sms_provider_username' => $this->sms_provider_username,
                'sms_provider_password' => $this->sms_provider_password,
                'sms_provider_senderid' => $this->sms_provider_senderid,
            ]);

        $this->closeModalAndFlashMessage(__('Setting for SMS successfully added!'),'');
    }
    public function saveSmtpConfig()
    {
        $setting = Setting::firstOr(function () {
            return Setting::create([
                'sms_provider_username' => $this->sms_provider_username,
                'sms_provider_password' => $this->sms_provider_password,
                'sms_provider_senderid' => $this->sms_provider_senderid,
            ]);
        });

        $this->closeModalAndFlashMessage(__('Setting for SMS successfully added!'),'');
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
