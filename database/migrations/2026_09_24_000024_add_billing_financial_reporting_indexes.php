<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('billing_refunds', function (Blueprint $table) {
            $table->index(['processed_at', 'status']);
        });

        Schema::table('billing_adjustments', function (Blueprint $table) {
            $table->index(['approved_at', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('billing_adjustments', function (Blueprint $table) {
            $table->dropIndex(['approved_at', 'status']);
        });

        Schema::table('billing_refunds', function (Blueprint $table) {
            $table->dropIndex(['processed_at', 'status']);
        });
    }
};
