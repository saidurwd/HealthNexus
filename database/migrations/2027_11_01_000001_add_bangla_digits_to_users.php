<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Additive (Phase 26, B7): a person may choose to see figures with Bangla digits (০১২৩) or with Latin digits, whatever the hospital's setting says. Null follows the hospital. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('bangla_digits')->nullable()->after('locale');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('bangla_digits');
        });
    }
};
