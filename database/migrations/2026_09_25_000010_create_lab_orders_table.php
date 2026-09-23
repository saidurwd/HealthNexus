<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('encounter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('clinical_order_id')->nullable()->constrained('clinical_orders')->nullOnDelete();
            $table->string('order_number');
            // routine|urgent|stat
            $table->string('priority')->default('routine');
            // ordered|registered|awaiting_collection|collected|received|processing|partial_result|
            // awaiting_validation|validated|reported|cancelled|rejected
            $table->string('status')->default('ordered');
            $table->foreignId('ordered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('ordered_at')->nullable();
            $table->timestamp('requested_collection_at')->nullable();
            $table->text('clinical_notes')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'order_number']);
            $table->index(['company_id', 'branch_id', 'status']);
            $table->index(['patient_id']);
            $table->index(['encounter_id']);
            $table->index(['clinical_order_id']);
        });

        Schema::create('lab_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lab_order_id')->constrained('lab_orders')->cascadeOnDelete();
            $table->foreignId('test_id')->nullable()->constrained('lab_tests')->nullOnDelete();
            $table->foreignId('panel_id')->nullable()->constrained('lab_panels')->nullOnDelete();
            // The free-text ClinicalOrderItem.item_name this row was matched (or failed to match)
            // against — kept even when test_id resolves, for traceability back to the original order.
            $table->string('requested_test_name');
            // routine|urgent|stat
            $table->string('priority')->default('routine');
            // unmatched|pending|awaiting_collection|collected|received|processing|resulted|
            // validated|cancelled|rejected
            $table->string('status')->default('pending');
            // pending|partial|completed
            $table->string('result_status')->default('pending');
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('collected_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['lab_order_id', 'status']);
            $table->index(['test_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_order_items');
        Schema::dropIfExists('lab_orders');
    }
};
