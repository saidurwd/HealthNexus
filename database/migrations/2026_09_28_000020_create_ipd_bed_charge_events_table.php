<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ipd_bed_charge_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admission_id')->constrained('ipd_admissions')->cascadeOnDelete();
            $table->foreignId('bed_id')->constrained('ipd_beds')->cascadeOnDelete();
            $table->date('charge_date');
            $table->timestamps();

            $table->unique(['admission_id', 'charge_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ipd_bed_charge_events');
    }
};
