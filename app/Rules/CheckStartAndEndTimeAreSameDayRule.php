<?php

namespace App\Rules;

use Carbon\Carbon;
use Illuminate\Contracts\Validation\Rule;

class CheckStartAndEndTimeAreSameDayRule implements Rule
{
    public $start_time;
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($start_time)
    {
        $this->start_time = $start_time;
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
        return Carbon::parse($value)->format('Y-m-d') === Carbon::parse($this->start_time)->format('Y-m-d');
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return __('Checkin and Checkout times must be same day.');
    }
}
