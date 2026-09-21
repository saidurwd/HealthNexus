<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('schedule_id')->constrained('doctor_schedules')->cascadeOnDelete();
            $table->dateTime('slot_datetime');
            $table->integer('duration_minutes')->default(15);
            $table->integer('max_capacity')->default(1);
            $table->integer('booked_count')->default(0);
            $table->enum('status', ['available', 'booked', 'blocked', 'cancelled'])->default('available');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'doctor_id', 'slot_datetime'], 'slot_datetime_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_slots');
    }
};
