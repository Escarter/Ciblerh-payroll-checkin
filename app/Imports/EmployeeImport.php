<?php

namespace App\Imports;

use App\Models\User;
use App\Models\Company;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use App\Events\EmployeeCreated;
use App\Models\Department;
use App\Models\Service;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToModel;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithValidation;
use App\Rules\PhoneNumber;
use App\Rules\ValidEmail;

class EmployeeImport implements ToModel, WithStartRow, SkipsEmptyRows, WithValidation, SkipsOnError, SkipsOnFailure
{
    use Importable, SkipsErrors, SkipsFailures;

    public $company;
    public $autoCreateEntities = false; // Whether to auto-create missing departments/services

    /**
     * @return int
     */
    public function startRow(): int
    {
        return 2;
    }


    public function __construct(Company $company, bool $autoCreateEntities = false)
    {
        $this->company = $company;
        $this->autoCreateEntities = $autoCreateEntities;
    }

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        // Validate that company exists and has an ID
        if (!$this->company || !$this->company->id) {
            throw new \Exception('Company is required for employee import');
        }

        $code_exist = User::where('email', $row[2])->first();

        // Validate email
        $emailValidation = validateEmail($row[2] ?? '');
        if (!$emailValidation['valid']) {
            throw new \Exception('Email validation failed: ' . $emailValidation['error']);
        }

        // Validate and format phone number
        $phoneNumber = preg_replace('/\s+/', '', $row[3] ?? '');
        if (empty($phoneNumber)) {
            throw new \Exception('Professional phone number is required');
        }
        
        $phoneValidation = validatePhoneNumber($phoneNumber);
        if (!$phoneValidation['valid']) {
            throw new \Exception('Professional phone number validation failed: ' . $phoneValidation['error']);
        }

        // Validate personal phone number if provided
        $personalPhoneNumber = null;
        if (isset($row[17]) && !empty($row[17])) {
            $personalPhone = preg_replace('/\s+/', '', $row[17]);
            $personalPhoneValidation = validatePhoneNumber($personalPhone);
            if (!$personalPhoneValidation['valid']) {
                throw new \Exception('Personal phone number validation failed: ' . $personalPhoneValidation['error']);
            }
            $personalPhoneNumber = $personalPhoneValidation['formatted'];
        }

        // Validate work times if provided
        $workStartTime = $this->parseTime($row[18] ?? null, '08:00');
        $workEndTime = $this->parseTime($row[19] ?? null, '17:30');

        if ($workStartTime && $workEndTime && $workStartTime >= $workEndTime) {
            throw new \Exception('Work start time must be before work end time');
        }

        // Skip if employee already exists
        if ($code_exist) {
            return null;
        }

        // Handle department lookup (by ID or name)
        $departmentResult = $this->findDepartment($row[9] ?? '');
        if (!$departmentResult['found']) {
            throw new \Exception('Department validation failed: ' . $departmentResult['error']);
        }

        // Handle service lookup (by ID or name)
        $serviceResult = $this->findService($row[10] ?? '', $departmentResult['department']->id);
        if (!$serviceResult['found']) {
            throw new \Exception('Service validation failed: ' . $serviceResult['error']);
        }

                $user = User::create([
                    'first_name' => $row[0],
                    'last_name' => $row[1],
                    'email' => $row[2],
                    'professional_phone_number' => $phoneValidation['formatted'],
                    'personal_phone_number' => $personalPhoneNumber,
                    'matricule' => $row[4],
                    'position' => $row[5],
                    'net_salary' => $row[6],
                    'salary_grade' => $row[7],
                    'contract_end' => $this->transformDate($row[8]),
                    'company_id' => $this->company->id,
                    'department_id' => $departmentResult['department']->id,
                    'service_id' => $serviceResult['service']->id,
                    'status' => $row[12],
                    'password' => bcrypt($row[13]),
                    'remaining_leave_days' => $row[14],
                    'monthly_leave_allocation' => $row[15],
                    'receive_sms_notifications' => isset($row[16]) ? (bool)$row[16] : true,
                    'receive_email_notifications' => isset($row[20]) ? (bool)$row[20] : true,
                    'alternative_email' => $row[21] ?? null,
                    'date_of_birth' => $this->transformDate($row[22] ?? null),
                    'work_start_time' => $workStartTime,
                    'work_end_time' => $workEndTime,
                    'author_id' => auth()->user()->id,
                    'pdf_password' => Str::random(10),
                ]);

                $user->assignRole($row[11]);

                event(new EmployeeCreated($user, $row[13]));

                return $user;
    }

    /**
     * Find department by ID or name
     */
    private function findDepartment($departmentValue): array
    {
        if (empty($departmentValue)) {
            return [
                'found' => false,
                'department' => null,
                'error' => __('Department is required')
            ];
        }

        // Try to find by ID first (if it's numeric)
        if (is_numeric($departmentValue)) {
            $department = Department::where('id', $departmentValue)
                ->where('company_id', $this->company->id)
                ->first();

            if ($department) {
                return [
                    'found' => true,
                    'department' => $department,
                    'error' => null
                ];
            }
        }

        // Try to find by name (using the helper function)
        $result = findOrCreateDepartment($departmentValue, $this->company->id, $this->autoCreateEntities);

        return $result;
    }

    /**
     * Find service by ID or name
     */
    private function findService($serviceValue, $departmentId): array
    {
        if (empty($serviceValue)) {
            return [
                'found' => false,
                'service' => null,
                'error' => __('Service is required')
            ];
        }

        // Try to find by ID first (if it's numeric)
        if (is_numeric($serviceValue)) {
            $service = Service::where('id', $serviceValue)
                ->where('department_id', $departmentId)
                ->first();

            if ($service) {
                return [
                    'found' => true,
                    'service' => $service,
                    'error' => null
                ];
            }
        }

        // Try to find by name (using the helper function)
        $result = findOrCreateService($serviceValue, $departmentId, $this->company->id, $this->autoCreateEntities);

        return $result;
    }

    /**
     * Parse time from Excel format or string
     */
    private function parseTime($value, $default = null)
    {
        if (empty($value)) {
            return $default;
        }

        try {
            // Try to parse as Excel time
            $dateTime = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value);
            return $dateTime->format('H:i');
        } catch (\Exception $e) {
            // Try to parse as time string
            try {
                return \Carbon\Carbon::createFromFormat('H:i', $value)->format('H:i');
            } catch (\Exception $e) {
                return $default;
            }
        }
    }

    public function transformDate($value, $format = 'Y-m-d')
    {
        if (empty($value)) {
            return null;
        }

        try {
            return \Carbon\Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value));
        } catch (\ErrorException $e) {
            return \Carbon\Carbon::createFromFormat($format, $value);
        }
    }

    public function rules(): array
    {
        return [
            '0' => 'required|string', // first_name
            '1' => 'required|string', // last_name
            '2' => ['required', new ValidEmail(), 'unique:users,email'], // email
            '3' => ['required', new PhoneNumber()], // professional_phone_number
            '4' => 'required|string', // matricule
            '5' => 'required|string', // position
            '6' => 'required|numeric', // net_salary
            '7' => 'required|string', // salary_grade
            '9' => 'required', // department (can be ID or name)
            '10' => 'required', // service (can be ID or name)
            '11' => function ($attribute, $value, $onFailure) {
                $array = ['employee', 'supervisor', 'manager'];
                if (!in_array(strtolower($value), $array)) {
                    $onFailure(__('Role must be one of: employee, supervisor, manager'));
                }
            },
            '18' => 'nullable|date_format:H:i', // work_start_time
            '19' => 'nullable|date_format:H:i', // work_end_time
            '21' => 'nullable|email', // alternative_email
            // Validate company context
            '*' => function ($attribute, $value, $onFailure) {
                if (!$this->company || !$this->company->id) {
                    $onFailure(__('Company context is required for employee import'));
                }
            }
        ];
    }
}
