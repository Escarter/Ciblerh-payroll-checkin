<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueEmailLocalPart implements ValidationRule
{
    public function __construct(
        protected ?int $ignoreUserId = null
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value) || trim($value) === '') {
            return;
        }

        $conflict = User::findConflictingEmailLocalPart($value, $this->ignoreUserId);

        if ($conflict) {
            $fail(__('employees.email_local_part_already_used', [
                'email' => $conflict->email,
            ]));
        }
    }
}
