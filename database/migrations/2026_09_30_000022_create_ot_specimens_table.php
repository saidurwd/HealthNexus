<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * clinical_order_id links to a Lab clinical order when the specimen needs processing — the
     * existing ClinicalOrder fan-out then creates the LabOrder/LabSpecimen (decision #10), no new
     * Lab-facing code needed.
     */
    public function up(): void
    {
        Schema::create('ot_specimens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('surgery_id')->constrained('ot_surgeries')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('encounter_id')->constrained('encounters')->cascadeOnDelete();
            $table->string('specimen_number');
            $table->string('specimen_type');
            $table->text('description')->nullable();
            $table->string('site')->nullable();
            $table->string('laterality')->nullable();
            $table->timestamp('collected_at');
            $table->foreignId('collected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('container')->nullable();
            $table->string('label')->nullable();
            $table->string('destination')->nullable();
            $table->foreignId('clinical_order_id')->nullable()->constrained('clinical_orders')->nullOnDelete();
            $table->timestamps();

            $table->unique(['organization_id', 'specimen_number']);
            $table->index('surgery_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ot_specimens');
    }
};
