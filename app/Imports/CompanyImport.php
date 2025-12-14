<?php

namespace App\Imports;

use App\Models\User;
use App\Models\Company;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithValidation;

class CompanyImport implements ToModel, WithStartRow, SkipsEmptyRows, WithValidation, SkipsOnError, SkipsOnFailure
{
    use Importable, SkipsErrors, SkipsFailures;
    

    /**
     * @return int
     */
    public function startRow(): int
    {
        return 2;
    }


    protected $userId;

    public function __construct(int $userId = null)
    {
        $this->userId = $userId ?? auth()->id() ?? 1;
    }
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        // Map columns from template: Name, Code, Address, City, Country, Phone, Email, Website, Is Active
        $name = $row[0] ?? '';
        $code = $row[1] ?? '';
        $address = $row[2] ?? '';
        $city = $row[3] ?? '';
        $country = $row[4] ?? '';

        // Check if company with this code already exists
        $code_exist = Company::where('code', $code)->first();
        if (!$code_exist && !empty($name)) {

            $company =  Company::create([
                'code' => $code == '' ? Str::upper(Str::random(12)) : $code,
                'name' => $name,
                'description' => trim(($address ? $address . ', ' : '') . ($city ? $city . ', ' : '') . $country, ', '),
                'sector' => $country, // Use country as sector for now, or could be left empty
                'author_id' => $this->userId,
            ]);
            return $company;
        }
    }
    public function rules(): array
    {
        return [
            '0' => 'required|string', // Name (required)
            '1' => 'nullable|string', // Code (nullable)
        ];
    }

    /**
     * Custom attribute names for validation error messages
     */
    public function customValidationAttributes(): array
    {
        return [
            '0' => __('companies.name'),
            '1' => __('companies.code'),
            '2' => __('common.address'),
            '3' => __('common.city'),
            '4' => __('common.country'),
        ];
    }
}
