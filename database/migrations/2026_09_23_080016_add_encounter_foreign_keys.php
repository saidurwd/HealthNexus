<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('encounters', function (Blueprint $table) {
            $table->foreign('encounter_type_id')->references('id')->on('encounter_types')->nullOnDelete();
            $table->foreign('specialty_id')->references('id')->on('specialties')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('encounters', function (Blueprint $table) {
            $table->dropForeign(['encounter_type_id']);
            $table->dropForeign(['specialty_id']);
        });
    }
};
