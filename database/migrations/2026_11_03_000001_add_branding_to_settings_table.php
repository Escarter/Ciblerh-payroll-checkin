<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('brand_app_name')->nullable();
            $table->string('brand_logo_path')->nullable();
            $table->string('brand_favicon_path')->nullable();
            $table->string('brand_primary_color', 9)->nullable();
            $table->string('brand_secondary_color', 9)->nullable();
            $table->string('brand_sidebar_color', 9)->nullable();
            $table->string('brand_emp_nav_color', 9)->nullable();
            $table->string('brand_emp_hero_color', 9)->nullable();
            $table->string('brand_page_bg_color', 9)->nullable();
            $table->string('brand_radius', 10)->nullable();
            $table->string('brand_font_family')->nullable();
            $table->string('brand_timezone')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'brand_app_name', 'brand_logo_path', 'brand_favicon_path', 'brand_primary_color',
                'brand_secondary_color', 'brand_sidebar_color', 'brand_emp_nav_color', 'brand_emp_hero_color', 'brand_page_bg_color', 'brand_radius', 'brand_font_family', 'brand_timezone',
            ]);
        });
    }
};
