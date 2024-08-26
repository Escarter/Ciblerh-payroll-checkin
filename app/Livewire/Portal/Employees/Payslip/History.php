<?php

namespace App\Livewire\Portal\Employees\Payslip;

use App\Models\User;
use App\Models\Payslip;
use App\Models\Setting;
use App\Services\Nexah;
use Livewire\Component;
use App\Mail\SendPayslip;
use App\Services\TwilioSMS;
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

    public function mount($employee_uuid)  
    {
        $this->employee = User::whereUuid($employee_uuid)->first();

    }

    public function initData($payslip_id)
    {
        if(!empty($payslip_id))
        {
            $this->payslip = Payslip::findOrFail($payslip_id);
        }
    }
    public function resendEmail()
    {
        if (!empty($this->payslip)) {
            $employee = User::findOrFail($this->payslip->employee->id);

        
                if (Storage::disk('modified')->exists($this->payslip->file)) {

                    $destination_file = $this->payslip->file;


                    if (!empty($employee->email)) {

                        try {
                            setSavedSmtpCredentials();

                            Mail::to(cleanString($employee->email))->send(new SendPayslip($employee, $destination_file, $this->payslip->month));

                            $this->payslip->update([
                                'email_sent_status' => Payslip::STATUS_SUCCESSFUL,
                            ]);

                            // sendSmsAndUpdateRecord($employee, $this->payslip->month, $this->payslip);

                            Log::info('mail-sent');
                            auditLog(
                                auth()->user(),
                                'send_sms',
                                'web',
                                'User <a href="/admin/users?user_id=' . auth()->user()->id . '">' . auth()->user()->name . '</a> send email to  <a href="/admin/groups/' . $employee->group_id . '/employees?employee_id=' . $employee->id . '">' . $employee->name . '</a>'
                            );

                            $this->closeModalAndFlashMessage(__('Email resent'), 'resendEmailModal');

                        } catch (\Swift_TransportException $e) {

                            Log::info('------> err swift:--  ' . $e->getMessage()); // for log, remove if you not want it
                            Log::info('' . PHP_EOL . '');
                            $this->payslip->update([
                                'email_sent_status' => Payslip::STATUS_FAILED,
                                'sms_sent_status' => Payslip::STATUS_FAILED,
                                'failure_reason' => $e->getMessage()
                            ]);
                        $this->closeModalAndFlashMessage(__('Email resent'), 'resendEmailModal');
                        } catch (\Swift_RfcComplianceException $e) {
                            Log::info('------> err Swift_Rfc:' . $e->getMessage());
                            Log::info('' . PHP_EOL . '');

                            $this->payslip->update([
                                'email_sent_status' => Payslip::STATUS_FAILED,
                                'sms_sent_status' => Payslip::STATUS_FAILED,
                                'failure_reason' => $e->getMessage()
                            ]);
                         $this->closeModalAndFlashMessage(__('Email resent'), 'resendEmailModal');
                        } catch (Exception $e) {
                            Log::info('------> err' . $e->getMessage());
                            Log::info('' . PHP_EOL . '');

                            $this->payslip->update([
                                'email_sent_status' => Payslip::STATUS_FAILED,
                                'sms_sent_status' => Payslip::STATUS_FAILED,
                                'failure_reason' => $e->getMessage()
                            ]);
                        $this->closeModalAndFlashMessage(__('Email resent'), 'resendEmailModal');
                        }
                    } else {
                        $this->payslip->update([
                            'email_sent_status' => Payslip::STATUS_FAILED,
                            'sms_sent_status' => Payslip::STATUS_FAILED,
                            'failure_reason' => __('No valid email address for User')
                        ]);
                    $this->closeModalAndFlashMessage(__('Email resent'), 'resendEmailModal');
                    }
                }
            
        }
    }

    public function resendSMS()
    {
        if(!empty($this->payslip)){

            $setting = Setting::first();

            $sms_client = match ($setting->sms_provider) {
                'twilio' => new TwilioSMS($setting),
                'nexah' =>  new Nexah($setting),
                default => new Nexah($setting)
            };

            if($sms_client->getBalance()['credit'] !== 0){
                $employee = User::findOrFail($this->payslip->employee->id);

                if (auth()->user()->hasRole('user') && $employee->group->user_id !== auth()->user()->id) {
                    return abort(401);
                }

                sendSmsAndUpdateRecord($employee, $this->payslip->month, $this->payslip);

                auditLog(
                    auth()->user(),
                    'send_sms',
                    'web',
                    'User <a href="/admin/users?user_id=' . auth()->user()->id . '">' . auth()->user()->name . '</a> send sms to  <a href="/admin/groups/' . $employee->group_id . '/employees?employee_id=' . $employee->id . '">' . $employee->name . '</a>'
                );
                $this->closeModalAndFlashMessage(__('SMS to :user successfully sent!', ['user' => $employee->name]), 'resendModal');
    
            }else{

                $this->closeModalAndFlashMessage(__('Insufficient SMS Balance'), 'resendSMSModal');
            }
         
        }

    }

    public function render()
    {
        if (!Gate::allows('payslip-read')) {
            return abort(401);
        }

        // if (auth()->user()->hasRole('employee') && $this->employee->department->author_id !== auth()->user()->id) {
        //     return abort(401);
        // }
        
        $payslips = Payslip::search($this->query)->where('employee_id', $this->employee->id)->orderBy($this->orderBy, $this->orderAsc)->paginate($this->perPage);
        $payslips_count = Payslip::where('employee_id', $this->employee->id)->count();

        return view('livewire.portal.employees.payslip.history', compact('payslips', 'payslips_count'))->layout('components.layouts.dashboard');
    }
}
