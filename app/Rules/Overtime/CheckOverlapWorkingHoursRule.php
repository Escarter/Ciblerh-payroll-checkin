<?php

namespace App\Rules\Overtime;

use Illuminate\Support\Carbon;
use Illuminate\Contracts\Validation\Rule;

class CheckOverlapWorkingHoursRule implements Rule
{
    
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
       
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        return Carbon::parse($value)->format('H:i')->gt(Carbon::parse(auth()->user()->work_start_time)->format('H:i')) && Carbon::parse(auth()->user()->work_end_time)->format('H:i')->lte(Carbon::parse($value)->format('H:i'));
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return __("Overtime can't be within working hours");
    }
}
