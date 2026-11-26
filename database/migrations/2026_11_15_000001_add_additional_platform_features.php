<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\PlatformFeature;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (class_exists(PlatformFeature::class)) {
            PlatformFeature::syncDefaultFeatures();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Keep records safe on rollback
    }
};
