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

        $code_exist = Company::where('code', $row[0])->first();
        if (!$code_exist ) {

            $company =  Company::create([
                'code' => $row[0] == '' ? Str::upper(Str::random(12)) : $row[0],
                'name' => $row[1],
                'description' => $row[2],
                'sector' => $row[3],
                'author_id' => auth()->user()->id,
            ]);
            return $company;
        }
    }
    public function rules(): array
    {
        return [
            '0' => 'required|string',
            '1' => 'required|string',
        ];
    }
}
