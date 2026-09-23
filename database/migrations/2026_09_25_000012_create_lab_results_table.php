<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lab_order_item_id')->constrained('lab_order_items')->cascadeOnDelete();
            $table->foreignId('test_id')->constrained('lab_tests')->cascadeOnDelete();
            $table->foreignId('specimen_id')->nullable()->constrained('lab_specimens')->nullOnDelete();

            // Mirrors lab_tests.test_type: quantitative|qualitative|semi_quantitative|text|
            // categorical|calculated|microbiology|culture|pathology — only the matching value
            // column below is populated, never forced into a single numeric field.
            $table->string('result_type');
            $table->decimal('numeric_value', 18, 4)->nullable();
            $table->text('text_value')->nullable();
            $table->string('qualitative_value')->nullable();
            $table->string('unit')->nullable();

            // Reference range snapshot at entry time — never re-derived from today's
            // lab_reference_ranges when displaying a historical result.
            $table->decimal('reference_range_low', 18, 4)->nullable();
            $table->decimal('reference_range_high', 18, 4)->nullable();
            $table->string('reference_range_text')->nullable();

            // normal|low|high|critical_low|critical_high|positive|negative|abnormal|panic|not_applicable
            $table->string('abnormal_flag')->nullable();
            $table->boolean('critical_flag')->default(false);

            // pending|entered|technically_validated|pathologist_validated|reported|amended|cancelled
            $table->string('result_status')->default('pending');

            $table->foreignId('entered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('entered_at')->nullable();
            $table->foreignId('technical_validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('technical_validated_at')->nullable();
            $table->foreignId('pathologist_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('pathologist_approved_at')->nullable();
            $table->timestamp('reported_at')->nullable();

            // Immutable versioning: an amendment creates a new row (version+1, amended_from_id
            // pointing at the row it supersedes) and flips the prior row's is_current to false —
            // the original is never overwritten.
            $table->unsignedInteger('version')->default(1);
            $table->boolean('is_current')->default(true);
            $table->foreignId('amended_from_id')->nullable()->constrained('lab_results')->nullOnDelete();
            $table->text('amendment_reason')->nullable();
            $table->foreignId('amended_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['lab_order_item_id', 'is_current']);
            $table->index(['company_id', 'branch_id', 'result_status']);
            $table->index(['critical_flag']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_results');
    }
};
