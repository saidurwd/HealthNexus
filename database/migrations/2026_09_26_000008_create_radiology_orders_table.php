<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('radiology_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('encounter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('clinical_order_id')->nullable()->constrained('clinical_orders')->nullOnDelete();
            $table->string('order_number');
            // Registration is folded into the order (Phase 6 plan decision #6) — no separate
            // radiology_registrations table. Distinct from Patient MRN and DICOM Study Instance UID.
            $table->string('accession_number')->nullable();
            // routine|urgent|stat
            $table->string('priority')->default('routine');
            // ordered|registered|scheduled|checked_in|preparing|ready|in_progress|completed|
            // images_available|reporting|reported|cancelled|no_show|rejected
            $table->string('status')->default('ordered');
            $table->foreignId('ordered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('ordered_at')->nullable();
            $table->foreignId('registered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('registered_at')->nullable();
            $table->text('clinical_indication')->nullable();
            $table->text('provisional_diagnosis')->nullable();
            $table->text('relevant_history')->nullable();
            $table->date('requested_date')->nullable();
            $table->boolean('contrast_required')->default(false);
            $table->text('special_instructions')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'order_number']);
            $table->unique(['company_id', 'accession_number']);
            $table->index(['company_id', 'branch_id', 'status']);
            $table->index(['patient_id']);
            $table->index(['encounter_id']);
            $table->index(['clinical_order_id']);
        });

        Schema::create('radiology_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('radiology_order_id')->constrained('radiology_orders')->cascadeOnDelete();
            $table->foreignId('procedure_id')->nullable()->constrained('radiology_procedures')->nullOnDelete();
            $table->foreignId('protocol_id')->nullable()->constrained('radiology_protocols')->nullOnDelete();
            $table->foreignId('body_part_id')->nullable()->constrained('radiology_body_parts')->nullOnDelete();
            // left|right|bilateral|midline|not_applicable
            $table->string('laterality')->nullable();
            // The free-text ClinicalOrderItem.item_name this row was matched (or failed to match)
            // against — kept even when procedure_id resolves, for traceability (mirrors Lab).
            $table->string('requested_procedure_name');
            $table->string('priority')->default('routine');
            // unmatched|pending|scheduled|checked_in|preparing|ready|in_progress|completed|
            // images_available|reporting|reported|cancelled
            $table->string('status')->default('pending');
            // pending|partial|completed
            $table->string('result_status')->default('pending');
            $table->timestamp('requested_at')->nullable();
            $table->timestamps();

            $table->index(['radiology_order_id', 'status']);
            $table->index(['procedure_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('radiology_order_items');
        Schema::dropIfExists('radiology_orders');
    }
};
