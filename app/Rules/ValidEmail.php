<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidEmail implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty(trim($value))) {
            $fail(__('validation.field_cannot_be_empty', ['attribute' => $attribute]));
            return;
        }

        $validation = validateEmail($value);

        if (!$validation['valid']) {
            $fail($validation['error'] ?? __('validation.email', ['attribute' => $attribute]));
        }
    }
}
