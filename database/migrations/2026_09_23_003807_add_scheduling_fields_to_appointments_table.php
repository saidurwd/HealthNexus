<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->foreignId('provider_id')->nullable()->after('doctor_id')->constrained()->nullOnDelete();
            $table->foreignId('appointment_type_id')->nullable()->after('type')->constrained()->nullOnDelete();
            // No FK yet — specialties table doesn't exist this early; see
            // add_specialty_foreign_keys_to_appointment_tables.
            $table->unsignedBigInteger('specialty_id')->nullable()->after('appointment_type_id');
            $table->foreignId('room_id')->nullable()->after('specialty_id')->constrained('appointment_rooms')->nullOnDelete();
            // Clinical urgency, distinct from the queue-serving priority on appointment_tokens
            // (Emergency/Priority/VIP/Regular/Follow-up, spec §28/§34). This column already
            // existed as a validated StoreAppointmentRequest field but had no backing column, so
            // it was silently discarded on every booking.
            $table->enum('priority', ['routine', 'urgent', 'stat'])->default('routine')->after('status');
            // Not positioned with ->after('referred_by') — that column is added later by
            // add_lifecycle_fields_to_appointments_table (2026_09_23_070000), which runs after
            // this migration on a fresh database.
            $table->string('referral_organization')->nullable();
            $table->string('referral_reference')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('provider_id');
            $table->dropConstrainedForeignId('appointment_type_id');
            $table->dropColumn('specialty_id');
            $table->dropConstrainedForeignId('room_id');
            $table->dropColumn(['priority', 'referral_organization', 'referral_reference']);
        });
    }
};
