<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Metadata only (Phase 6 plan decision #3) — no DICOM binary/pixel data is ever stored here.
 * Modalities send images directly to PACS; this table records identifiers/descriptions reconciled
 * in from the PACS adapter's queryStudies().
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('radiology_studies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('radiology_examination_id')->constrained('radiology_examinations')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pacs_server_id')->nullable()->constrained('radiology_pacs_servers')->nullOnDelete();
            // Application-level identifier — never confused with the DICOM Study Instance UID
            // (spec §21).
            $table->string('accession_number');
            // DICOM identifier, supplied by the modality/PACS. Nullable until reconciled.
            $table->string('study_instance_uid')->nullable();
            $table->string('study_id')->nullable();
            $table->date('study_date')->nullable();
            $table->time('study_time')->nullable();
            $table->string('modality')->nullable();
            $table->string('study_description')->nullable();
            $table->string('body_part')->nullable();
            $table->string('referring_physician')->nullable();
            $table->string('institution_name')->nullable();
            // pending|found|not_found|error
            $table->string('pacs_status')->default('pending');
            // unmatched|matched|duplicate_flagged|reconciled
            $table->string('study_status')->default('unmatched');
            $table->unsignedInteger('number_of_series')->default(0);
            $table->unsignedInteger('number_of_instances')->default(0);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique('study_instance_uid');
            $table->index(['company_id', 'branch_id', 'study_status']);
            $table->index(['radiology_examination_id']);
            $table->index(['accession_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('radiology_studies');
    }
};
