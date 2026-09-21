<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('google2fa_secret')->nullable()->after('password');
            $table->boolean('mfa_enabled')->default(false)->after('google2fa_secret');
            $table->timestamp('mfa_confirmed_at')->nullable()->after('mfa_enabled');
            $table->text('mfa_recovery_codes')->nullable()->after('mfa_confirmed_at');
            $table->timestamp('mfa_last_used_at')->nullable()->after('mfa_recovery_codes');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'google2fa_secret',
                'mfa_enabled',
                'mfa_confirmed_at',
                'mfa_recovery_codes',
                'mfa_last_used_at',
            ]);
        });
    }
};
