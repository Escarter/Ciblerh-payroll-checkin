<?php

namespace App\Livewire\Portal\Payslips;

use App\Models\User;
use App\Models\Payslip;
use Livewire\Component;
use App\Mail\SendPayslip;
use App\Models\SendPayslipProcess;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Livewire\Traits\WithDataTable;
use Illuminate\Support\Facades\Storage;

class Details extends Component
{
    use WithDataTable;

    public $job;
    public ?Payslip $payslip;

    public function mount($id)
    {
        $this->job = SendPayslipProcess::findOrFail($id);
    }

    public function initData($payslip_id)
    {
        if (!empty($payslip_id)) {
            $this->payslip = Payslip::findOrFail($payslip_id);
        }
    }

    public function resendPayslip()
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

                        sendSmsAndUpdateRecord($employee, $this->payslip->month, $this->payslip);

                        Log::info('mail-sent');

                        $this->closeModalAndFlashMessage(__('Employee Payslip resent successfully'), 'resendPayslipModal');
                    } catch (\Swift_TransportException $e) {

                        Log::info('------> err swift:--  ' . $e->getMessage()); // for log, remove if you not want it
                        Log::info('' . PHP_EOL . '');
                        $this->payslip->update([
                            'email_sent_status' => Payslip::STATUS_FAILED,
                            'sms_sent_status' => Payslip::STATUS_FAILED,
                            'failure_reason' => $e->getMessage()
                        ]);

                    } catch (\Swift_RfcComplianceException $e) {
                        Log::info('------> err Swift_Rfc:' . $e->getMessage());
                        Log::info('' . PHP_EOL . '');

                        $this->payslip->update([
                            'email_sent_status' => Payslip::STATUS_FAILED,
                            'sms_sent_status' => Payslip::STATUS_FAILED,
                            'failure_reason' => $e->getMessage()
                        ]);
                    } catch (\Exception $e) {
                        Log::info('------> err' . $e->getMessage());
                        Log::info('' . PHP_EOL . '');

                        $this->payslip->update([
                            'email_sent_status' => Payslip::STATUS_FAILED,
                            'sms_sent_status' => Payslip::STATUS_FAILED,
                            'failure_reason' => $e->getMessage()
                        ]);
                    }
                } else {
                    $this->payslip->update([
                        'email_sent_status' => Payslip::STATUS_FAILED,
                        'sms_sent_status' => Payslip::STATUS_FAILED,
                        'failure_reason' => __('No valid email address for User')
                    ]);
                }
            }
        }
    }

    public function render()
    {
        $payslip_details = Payslip::search($this->query)->where('send_payslip_process_id',$this->job->id)->orderBy($this->orderBy, $this->orderAsc)->paginate($this->perPage);

        return view('livewire.portal.payslips.details', [
            'payslips' => $payslip_details,
            'payslips_count' => count($this->job->payslips),
            'job' => $this->job
        ])->layout('components.layouts.dashboard');
    }
}
