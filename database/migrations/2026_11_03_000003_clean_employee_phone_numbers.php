<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['professional_phone_number', 'personal_phone_number'] as $column) {
            DB::table('users')
                ->whereNotNull($column)
                ->orderBy('id')
                ->each(function ($user) use ($column) {
                    $clean = preg_replace('/[^0-9+]/', '', (string) $user->{$column});
                    if ($clean === (string) $user->{$column}) {
                        return;
                    }
                    DB::table('users')->where('id', $user->id)->update([$column => $clean ?: null]);
                });
        }
    }

    public function down(): void
    {
    }
};
