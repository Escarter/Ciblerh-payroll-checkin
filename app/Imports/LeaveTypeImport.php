<?php

namespace App\Imports;

use App\Models\LeaveType;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithValidation;

class LeaveTypeImport implements ToModel, WithStartRow, SkipsEmptyRows, WithValidation, SkipsOnError, SkipsOnFailure
{
    use Importable, SkipsErrors, SkipsFailures;

    /**
     * @return int
     */
    public function startRow(): int
    {
        return 2;
    }

    public function __construct()
    {
        
    }

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        $code_exist = LeaveType::where('name', $row[0])->first();
        if (!$code_exist) {
            return new LeaveType([
                'name' => $row[0],
                'description' => $row[1] ?? '',
                'default_number_of_days' => $row[2] ?? 0,
                'is_active' => $row[3] ?? true,
                'author_id' => auth()->user()->id,
            ]);
        }
    }

    public function rules(): array
    {
        return [
            '0' => 'required|string',
            '2' => 'nullable|integer|min:0',
            '3' => 'nullable|boolean',
        ];
    }
}
