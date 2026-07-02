<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueMatricule implements ValidationRule
{
    public function __construct(
        protected ?int $ignoreUserId = null,
        protected ?string $email = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value) || trim($value) === '') {
            return;
        }

        $conflict = User::findConflictingMatricule($value, $this->email, $this->ignoreUserId);

        if ($conflict) {
            $fail(__('employees.matricule_already_used_by', [
                'email' => $conflict->email,
            ]));
        }
    }
}
