<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            if (! Schema::hasColumn('settings', 'brand_auth_bg_path')) {
                $table->string('brand_auth_bg_path')->nullable();
            }
            if (! Schema::hasColumn('settings', 'brand_auth_kicker')) {
                $table->string('brand_auth_kicker', 120)->nullable();
            }
            if (! Schema::hasColumn('settings', 'brand_auth_title')) {
                $table->string('brand_auth_title', 200)->nullable();
            }
            if (! Schema::hasColumn('settings', 'brand_auth_subtitle')) {
                $table->text('brand_auth_subtitle')->nullable();
            }
            if (! Schema::hasColumn('settings', 'brand_auth_feature_1')) {
                $table->string('brand_auth_feature_1', 200)->nullable();
            }
            if (! Schema::hasColumn('settings', 'brand_auth_feature_2')) {
                $table->string('brand_auth_feature_2', 200)->nullable();
            }
            if (! Schema::hasColumn('settings', 'brand_auth_feature_3')) {
                $table->string('brand_auth_feature_3', 200)->nullable();
            }
            if (! Schema::hasColumn('settings', 'brand_auth_footer')) {
                $table->string('brand_auth_footer', 200)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $columns = array_filter([
                'brand_auth_bg_path',
                'brand_auth_kicker',
                'brand_auth_title',
                'brand_auth_subtitle',
                'brand_auth_feature_1',
                'brand_auth_feature_2',
                'brand_auth_feature_3',
                'brand_auth_footer',
            ], fn (string $col) => Schema::hasColumn('settings', $col));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
