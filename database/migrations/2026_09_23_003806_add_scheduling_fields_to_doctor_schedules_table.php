<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A doctor previously could have only one schedule row per day of week system-wide,
        // contradicting the spec's own "Morning Clinic / Evening Clinic / Follow-up Clinic"
        // example. Replaced with a constraint that only blocks an exact duplicate (same doctor,
        // day, and start time) — genuine time-range overlap between two differently-timed
        // sessions is validated in ScheduleGenerationService instead, since MySQL has no portable
        // range-exclusion constraint. The new index is added before the old one is dropped
        // because MySQL silently reuses doctor_schedule_unique as the support index for the
        // company_id foreign key — dropping it first fails with "needed in a foreign key
        // constraint" unless a replacement leading-company_id index already exists.
        Schema::table('doctor_schedules', function (Blueprint $table) {
            $table->unique(['company_id', 'doctor_id', 'day_of_week', 'start_time'], 'doctor_schedule_session_unique');
        });

        Schema::table('doctor_schedules', function (Blueprint $table) {
            $table->dropUnique('doctor_schedule_unique');
        });

        Schema::table('doctor_schedules', function (Blueprint $table) {
            $table->foreignId('provider_id')->nullable()->after('doctor_id')->constrained()->nullOnDelete();
            // No FK yet — specialties table doesn't exist this early; see
            // add_specialty_foreign_keys_to_appointment_tables.
            $table->unsignedBigInteger('specialty_id')->nullable()->after('department_id');
            $table->foreignId('room_id')->nullable()->after('specialty_id')->constrained('appointment_rooms')->nullOnDelete();
            $table->date('effective_from')->nullable()->after('end_time');
            $table->date('effective_to')->nullable()->after('effective_from');
            $table->unsignedInteger('buffer_minutes')->default(0)->after('slot_duration_minutes');
            $table->time('break_start_time')->nullable()->after('buffer_minutes');
            $table->time('break_end_time')->nullable()->after('break_start_time');
            $table->unsignedInteger('default_capacity_per_slot')->default(1)->after('break_end_time');
            $table->unsignedInteger('overbooking_limit')->default(0)->after('default_capacity_per_slot');
            $table->json('appointment_type_ids')->nullable()->after('overbooking_limit');
        });
    }

    public function down(): void
    {
        Schema::table('doctor_schedules', function (Blueprint $table) {
            $table->unique(['company_id', 'doctor_id', 'day_of_week'], 'doctor_schedule_unique');
        });

        Schema::table('doctor_schedules', function (Blueprint $table) {
            $table->dropUnique('doctor_schedule_session_unique');
            $table->dropConstrainedForeignId('provider_id');
            $table->dropColumn('specialty_id');
            $table->dropConstrainedForeignId('room_id');
            $table->dropColumn([
                'effective_from', 'effective_to', 'buffer_minutes', 'break_start_time', 'break_end_time',
                'default_capacity_per_slot', 'overbooking_limit', 'appointment_type_ids',
            ]);
        });
    }
};
