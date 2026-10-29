<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Phase 18, part 2: results that arrive from outside wait here for a person (HL7 ORU), and DICOM modality worklist entries made from radiology exams (S08, S09). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hl7_inbound_results', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('message_id')->constrained('integration_messages')->cascadeOnDelete();
            $table->foreignId('patient_id')->nullable()->constrained()->nullOnDelete();
            $table->string('patient_ref', 80)->nullable(); // the patient identifier as the sender wrote it
            $table->string('placer_order_no', 60)->nullable();
            $table->string('filler_order_no', 60)->nullable();
            $table->string('code', 60);
            $table->string('name', 200)->nullable();
            $table->string('value', 500)->nullable();
            $table->string('unit', 40)->nullable();
            $table->string('reference_range', 80)->nullable();
            $table->string('abnormal_flag', 10)->nullable();
            $table->timestamp('observed_at')->nullable();
            $table->string('status', 9)->default('new'); // new | accepted | rejected
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('note', 300)->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
        });

        Schema::create('dicom_worklist_entries', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('examination_id')->unique()->constrained('radiology_examinations')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('accession_number', 60);
            $table->string('patient_name', 200); // DICOM person name: FAMILY^GIVEN^MIDDLE
            $table->string('patient_mrn', 60);
            $table->date('birth_date')->nullable();
            $table->char('sex', 1)->nullable(); // M | F | O
            $table->string('modality', 8);
            $table->string('procedure_description', 200)->nullable();
            $table->string('requested_procedure_id', 40)->nullable();
            $table->string('station_ae_title', 16);
            $table->timestamp('scheduled_at')->nullable();
            $table->string('referring_physician', 120)->nullable();
            $table->string('status', 11)->default('scheduled'); // scheduled | in_progress | completed | cancelled
            $table->timestamps();

            $table->index(['organization_id', 'status', 'scheduled_at']);
            $table->index(['station_ae_title', 'modality', 'scheduled_at'], 'dicom_worklist_query_index');
            $table->index('accession_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dicom_worklist_entries');
        Schema::dropIfExists('hl7_inbound_results');
    }
};
