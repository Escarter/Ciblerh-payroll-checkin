<?php

namespace App\Support;

use App\Models\User;
use App\Rules\UniqueEmailLocalPart;
use App\Rules\UniqueMatricule;
use App\Rules\ValidEmail;
use Illuminate\Validation\Rule;

class EmployeeIdentityRules
{
    public static function email(?int $ignoreUserId = null): array
    {
        $unique = Rule::unique(User::class, 'email');
        if ($ignoreUserId) {
            $unique->ignore($ignoreUserId);
        }

        return [
            'required',
            new ValidEmail(),
            $unique,
            new UniqueEmailLocalPart($ignoreUserId),
        ];
    }

    public static function matricule(?int $ignoreUserId = null, ?string $email = null): array
    {
        return [
            'required',
            new UniqueMatricule($ignoreUserId, $email),
        ];
    }
}
