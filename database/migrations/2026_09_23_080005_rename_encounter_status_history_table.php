<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('encounter_status_history', 'encounter_status_histories');
    }

    public function down(): void
    {
        Schema::rename('encounter_status_histories', 'encounter_status_history');
    }
};
