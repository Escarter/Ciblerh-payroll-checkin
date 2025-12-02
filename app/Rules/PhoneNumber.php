<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PhoneNumber implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value)) {
            return; // Let 'required' rule handle empty values
        }

        $validation = validatePhoneNumber($value);
        
        if (!$validation['valid']) {
            $fail($validation['error'] ?? __('The :attribute must be a valid phone number in E.164 format (e.g., +1234567890)'));
        }
    }
}
