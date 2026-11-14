<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'mfa_enabled')) {
                $table->boolean('mfa_enabled')->default(false)->after('status');
            }
            if (! Schema::hasColumn('users', 'mfa_required')) {
                $table->boolean('mfa_required')->default(false)->after('mfa_enabled');
            }
            if (! Schema::hasColumn('users', 'mfa_method')) {
                $table->string('mfa_method', 32)->nullable()->after('mfa_required');
            }
            if (! Schema::hasColumn('users', 'mfa_secret')) {
                $table->text('mfa_secret')->nullable()->after('mfa_method');
            }
            if (! Schema::hasColumn('users', 'mfa_phone')) {
                $table->string('mfa_phone', 32)->nullable()->after('mfa_secret');
            }
            if (! Schema::hasColumn('users', 'mfa_totp_timestep')) {
                $table->bigInteger('mfa_totp_timestep')->nullable()->after('mfa_phone');
            }
            if (! Schema::hasColumn('users', 'mfa_confirmed_at')) {
                $table->timestamp('mfa_confirmed_at')->nullable()->after('mfa_totp_timestep');
            }
            if (! Schema::hasColumn('users', 'mfa_last_used_at')) {
                $table->timestamp('mfa_last_used_at')->nullable()->after('mfa_confirmed_at');
            }
        });

        Schema::table('settings', function (Blueprint $table) {
            if (! Schema::hasColumn('settings', 'mfa_required_for')) {
                $table->string('mfa_required_for', 32)->default('none');
            }
            if (! Schema::hasColumn('settings', 'mfa_authenticator_enabled')) {
                $table->boolean('mfa_authenticator_enabled')->default(true);
            }
            if (! Schema::hasColumn('settings', 'mfa_email_enabled')) {
                $table->boolean('mfa_email_enabled')->default(true);
            }
            if (! Schema::hasColumn('settings', 'mfa_sms_enabled')) {
                $table->boolean('mfa_sms_enabled')->default(true);
            }
            if (! Schema::hasColumn('settings', 'mfa_code_minutes')) {
                $table->unsignedSmallInteger('mfa_code_minutes')->default(10);
            }
            if (! Schema::hasColumn('settings', 'mfa_max_attempts')) {
                $table->unsignedSmallInteger('mfa_max_attempts')->default(5);
            }
            if (! Schema::hasColumn('settings', 'mfa_remember_days')) {
                $table->unsignedSmallInteger('mfa_remember_days')->default(30);
            }
        });

        if (! Schema::hasTable('mfa_recovery_codes')) {
            Schema::create('mfa_recovery_codes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('code_hash', 64)->index();
                $table->timestamp('used_at')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'used_at']);
            });
        }

        if (! Schema::hasTable('mfa_security_events')) {
            Schema::create('mfa_security_events', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->unsignedBigInteger('actor_id')->nullable();
                $table->string('event', 64)->index();
                $table->string('method', 32)->nullable();
                $table->string('status', 32)->default('success');
                $table->string('reason', 255)->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->string('user_agent', 500)->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'created_at']);
            });
        }

        if (! Schema::hasTable('mfa_trusted_devices')) {
            Schema::create('mfa_trusted_devices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('name', 120)->nullable();
                $table->string('token_hash', 64)->index();
                $table->string('ip_address', 45)->nullable();
                $table->string('user_agent', 500)->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamp('expires_at')->index();
                $table->timestamps();

                $table->index(['user_id', 'expires_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('mfa_trusted_devices');
        Schema::dropIfExists('mfa_security_events');
        Schema::dropIfExists('mfa_recovery_codes');

        Schema::table('settings', function (Blueprint $table) {
            $columns = array_filter([
                'mfa_required_for',
                'mfa_authenticator_enabled',
                'mfa_email_enabled',
                'mfa_sms_enabled',
                'mfa_code_minutes',
                'mfa_max_attempts',
                'mfa_remember_days',
            ], fn (string $col) => Schema::hasColumn('settings', $col));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });

        Schema::table('users', function (Blueprint $table) {
            $columns = array_filter([
                'mfa_enabled',
                'mfa_required',
                'mfa_method',
                'mfa_secret',
                'mfa_phone',
                'mfa_totp_timestep',
                'mfa_confirmed_at',
                'mfa_last_used_at',
            ], fn (string $col) => Schema::hasColumn('users', $col));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
