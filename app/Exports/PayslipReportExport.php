<?php

namespace App\Exports;

use App\Models\User;
use App\Models\Payslip;
use App\Models\Department;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class PayslipReportExport implements FromQuery, WithMapping, WithHeadings
{
    use Exportable;

    public $company;
    public $query;
    public $selectedCompanyId;
    public $selectedDepartmentId;
    public $employee_id;
    public $start_date;
    public $end_date;
    public $email_status;
    public $sms_status;
    public $query_string;
    public $auth_role;

    public function __construct($selectedCompanyId, $selectedDepartmentId, $employee_id,$start_date,$end_date,$email_status, $sms_status, $query_string = '')
    {
        $this->selectedCompanyId = $selectedCompanyId;
        $this->selectedDepartmentId = $selectedDepartmentId;
        $this->query_string = $query_string;
        $this->employee_id = $employee_id;
        $this->start_date =  $start_date;
        $this->end_date =  $end_date;
        $this->email_status =  $email_status;
        $this->sms_status =  $sms_status;
        $this->auth_role = auth()->user()->getRoleNames()->first();
    }

    public function headings(): array
    {
        return [
            'Company',
            'Department',
            'service',
            'Matricule',
            'First Name',
            'Last Name',
            'Email',
            'Phone Number',
            'Month',
            'Year',
            'SMS status',
            'Email Status',
            'Created Date',
        ];
    }

    public function query()
    {
        return Payslip::query()
            ->when(!empty($this->query_string), function ($q) {
                return
                    $q->where('first_name', 'like', '%' . $this->query_string . '%')
                    ->orWhere('last_name', 'like', '%' . $this->query_string . '%')
                    ->orWhere('email', 'like', '%' . $this->query_string . '%')
                    ->orWhere('matricule', 'like', '%' . $this->query_string . '%')
                    ->orWhere('phone', 'like', '%' . $this->query_string . '%')
                    ->orWhere('month', 'like', '%' . $this->query_string . '%')
                    ->orWhere('email_sent_status', 'like', '%' . $this->query_string . '%')
                    ->orWhere('sms_sent_status', 'like', '%' . $this->query_string . '%');
            })->when($this->selectedCompanyId != "all" && $this->auth_role !== 'supervisor', function ($query) {
                return $query->where('company_id',  $this->selectedCompanyId);
            })->when($this->selectedDepartmentId != "all", function ($query) {
                return $query->where('department_id',  $this->selectedDepartmentId);
            })->when($this->employee_id != "all", function ($query) {
                return $query->where('employee_id',  $this->employee_id);
            })->when(!empty($this->email_status) && $this->email_status === 'all', function ($query) {
                return $query->where('email_sent_status', Payslip::STATUS_SUCCESSFUL)
                    ->orWhere('email_sent_status', Payslip::STATUS_PENDING)
                    ->orWhere('email_sent_status', Payslip::STATUS_FAILED);
            })->when(!empty($this->sms_status) && $this->sms_status === 'all', function ($query) {
                return $query->where('sms_sent_status', Payslip::STATUS_SUCCESSFUL)
                    ->orWhere('sms_sent_status', Payslip::STATUS_PENDING)
                    ->orWhere('sms_sent_status', Payslip::STATUS_FAILED);
            })->when(!empty($this->email_status) && $this->email_status == Payslip::STATUS_SUCCESSFUL, function ($query) {
                return $query->where('email_sent_status', Payslip::STATUS_SUCCESSFUL);
            })->when(!empty($this->email_status) && $this->email_status == Payslip::STATUS_PENDING, function ($query) {
                return $query->where('email_sent_status', Payslip::STATUS_PENDING);
            })->when(!empty($this->email_status) && $this->email_status == Payslip::STATUS_FAILED, function ($query) {
                return $query->where('email_sent_status',  Payslip::STATUS_FAILED);
            })->when(!empty($this->sms_status) && $this->sms_status == Payslip::SMS_STATUS_SUCCESSFUL, function ($query) {
                return $query->where('sms_sent_status', Payslip::STATUS_SUCCESSFUL);
            })->when(!empty($this->sms_status) && $this->sms_status == Payslip::SMS_STATUS_PENDING, function ($query) {
                return $query->where('sms_sent_status', Payslip::STATUS_PENDING);
            })->when(!empty($this->sms_status) && $this->sms_status == Payslip::SMS_STATUS_FAILED, function ($query) {
                return $query->where('sms_sent_status',  Payslip::STATUS_FAILED);
            })->when(!empty($this->start_date) || !empty($this->end_date), function ($query) {
                return $query->whereBetween(DB::raw('date(created_at)'), [Carbon::parse($this->start_date)->toDateString(), Carbon::parse($this->end_date)->toDateString()]);
            });
    }

    /**
     * @var Payslip $payslip
     */
    public function map($payslip): array
    {

        return [
            !is_null($payslip->company) ? $payslip->company->name : '',
            !is_null($payslip->department) ? $payslip->department->name : '',
            !is_null($payslip->service) ? $payslip->service->name : '',
            $payslip->matricule,
            $payslip->first_name,
            $payslip->last_name,
            $payslip->email,
            $payslip->phone,
            $payslip->month,
            $payslip->year,
            $payslip->email_status_text,
            $payslip->sms_status_text,
            Date::dateTimeToExcel($payslip->created_at),
        ];
    }
}
