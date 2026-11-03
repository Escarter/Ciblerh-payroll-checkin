<?php

namespace App\Livewire\Traits;

use Illuminate\Support\Facades\Gate;

trait AuthorizesAbilities
{
    /**
     * Abort with 401 unless the user holds the ability (or any, when given an array).
     */
    protected function authorizeAbility(string|array $abilities): void
    {
        foreach ((array) $abilities as $ability) {
            if (Gate::allows($ability)) {
                return;
            }
        }

        abort(401);
    }
}
