<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('encounters', function (Blueprint $table) {
            $table->unsignedBigInteger('appointment_id')->nullable()->after('patient_id');
            $table->foreign('appointment_id')->references('id')->on('appointments')->nullOnDelete();
            $table->unsignedBigInteger('encounter_type_id')->nullable()->after('encounter_type');
            $table->foreign('encounter_type_id')->references('id')->on('encounter_types')->nullOnDelete();
            $table->unsignedBigInteger('provider_id')->nullable()->after('department_id');
            $table->foreign('provider_id')->references('id')->on('users')->nullOnDelete();
            $table->unsignedBigInteger('specialty_id')->nullable()->after('provider_id');
            $table->foreign('specialty_id')->references('id')->on('specialties')->nullOnDelete();
            $table->date('encounter_date')->nullable()->after('encounter_no');
            $table->string('priority')->nullable()->after('status');
            $table->string('source')->nullable()->after('priority');
            $table->text('chief_complaint_summary')->nullable()->after('source');
            $table->text('reason_for_visit')->nullable()->after('chief_complaint_summary');
            $table->string('referred_by')->nullable()->after('reason_for_visit');
            $table->unsignedBigInteger('created_by')->nullable()->after('notes');
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->unsignedBigInteger('completed_by')->nullable()->after('created_by');
            $table->foreign('completed_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable()->after('completed_by');
            $table->timestamp('locked_at')->nullable()->after('completed_at');
            $table->unsignedBigInteger('locked_by')->nullable()->after('locked_at');
            $table->foreign('locked_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['company_id', 'encounter_date']);
            $table->index(['patient_id', 'encounter_date']);
            $table->index(['provider_id', 'encounter_date']);
        });
    }

    public function down(): void
    {
        Schema::table('encounters', function (Blueprint $table) {
            $table->dropForeign(['appointment_id']);
            $table->dropForeign(['encounter_type_id']);
            $table->dropForeign(['provider_id']);
            $table->dropForeign(['specialty_id']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['completed_by']);
            $table->dropForeign(['locked_by']);
            $table->dropIndex(['company_id', 'encounter_date']);
            $table->dropIndex(['patient_id', 'encounter_date']);
            $table->dropIndex(['provider_id', 'encounter_date']);

            $table->dropColumn([
                'appointment_id',
                'encounter_type_id',
                'provider_id',
                'specialty_id',
                'encounter_date',
                'priority',
                'source',
                'chief_complaint_summary',
                'reason_for_visit',
                'referred_by',
                'created_by',
                'completed_by',
                'completed_at',
                'locked_at',
                'locked_by',
            ]);
        });
    }
};
