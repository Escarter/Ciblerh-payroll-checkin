<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Keep one row per matricule (active first, then newest) and tag the rest so the index can be created.
        $duplicates = DB::table('users')->select('matricule')->groupBy('matricule')->havingRaw('count(*) > 1')->pluck('matricule');
        foreach ($duplicates as $matricule) {
            $ids = DB::table('users')->where('matricule', $matricule)
                ->orderByRaw('deleted_at is not null')->orderByDesc('id')->pluck('id');
            foreach ($ids->slice(1) as $id) {
                DB::table('users')->where('id', $id)->update(['matricule' => $matricule . '-DUP' . $id]);
            }
        }

        Schema::table('users', function (Blueprint $table) {
            $table->unique('matricule');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['matricule']);
        });
    }
};
