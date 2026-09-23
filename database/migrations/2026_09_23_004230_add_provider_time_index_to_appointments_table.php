<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Not a unique constraint — a slot can legitimately hold multiple appointments (capacity >
     * 1), so uniqueness on (provider, date, time) alone would be wrong. This index instead makes
     * the gap-lock taken by AppointmentBookingService's `lockForUpdate()` conflict-check
     * (SELECT ... FOR UPDATE on provider+date+time, used for the no-slot/free-text booking path)
     * efficient — InnoDB still takes a correct gap lock without an index, just a slower one.
     */
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->index(['company_id', 'provider_id', 'appointment_date', 'appointment_time'], 'appointment_provider_datetime_idx');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex('appointment_provider_datetime_idx');
        });
    }
};
