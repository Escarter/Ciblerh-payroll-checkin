<?php

namespace App\Imports;

use App\Models\Holiday;
use App\Models\Company;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithValidation;

class HolidayImport implements ToModel, WithStartRow, SkipsEmptyRows, WithValidation, SkipsOnError, SkipsOnFailure
{
    use Importable, SkipsErrors, SkipsFailures;

    public $userId;
    public $companyId;

    /**
     * @return int
     */
    public function startRow(): int
    {
        return 2;
    }

    public function __construct($userId = null, $companyId = null)
    {
        $this->userId = $userId;
        $this->companyId = $companyId;
    }

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        try {
            // Parse date - support multiple formats
            $dateValue = $row[0] ?? null;
            if (!$dateValue) {
                return null;
            }

            try {
                // Try to parse as various formats
                $date = Carbon::createFromFormat('Y-m-d', $dateValue)
                    ?? Carbon::createFromFormat('d/m/Y', $dateValue)
                    ?? Carbon::createFromFormat('m/d/Y', $dateValue)
                    ?? Carbon::parse($dateValue);
            } catch (\Exception $e) {
                \Log::warning('Holiday import: Unable to parse date', ['value' => $dateValue]);
                return null;
            }

            $name = $row[1] ?? '';
            $description = $row[2] ?? '';
            $companyId = $row[3] ?? ($this->companyId ?? null);
            
            // Parse company_id if it's a numeric string
            if (is_string($companyId) && is_numeric($companyId)) {
                $companyId = (int) $companyId;
            }

            // Validate company exists if provided
            if ($companyId) {
                $company = Company::find($companyId);
                if (!$company) {
                    \Log::warning('Holiday import: Company not found', ['company_id' => $companyId]);
                    return null;
                }
            }

            // Check if holiday already exists for this date and company
            $exists = Holiday::where('date', $date->format('Y-m-d'))
                ->where(function ($q) use ($companyId) {
                    if ($companyId) {
                        $q->where('company_id', $companyId);
                    } else {
                        $q->whereNull('company_id');
                    }
                })
                ->exists();

            if ($exists) {
                // Skip duplicates
                return null;
            }

            return new Holiday([
                'date' => $date,
                'name' => $name,
                'description' => $description,
                'company_id' => $companyId,
                'is_recurring' => false,
            ]);
        } catch (\Exception $e) {
            \Log::error('Holiday import error', ['error' => $e->getMessage(), 'row' => $row]);
            return null;
        }
    }

    public function rules(): array
    {
        return [
            '0' => 'required|date_format:Y-m-d',
            '1' => 'required|string|max:255',
            '2' => 'nullable|string|max:1000',
            '3' => 'nullable|integer|exists:companies,id',
        ];
    }

    /**
     * Custom attribute names for validation error messages
     */
    public function customValidationAttributes(): array
    {
        return [
            '0' => __('common.date'),
            '1' => __('common.name'),
            '2' => __('common.description'),
            '3' => __('companies.company'),
        ];
    }
}
