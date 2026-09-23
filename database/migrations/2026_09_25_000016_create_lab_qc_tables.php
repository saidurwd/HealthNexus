<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Foundation only — plain CRUD records, no Levey-Jennings/Westgard statistical engine.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_qc_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('section_id')->nullable()->constrained('lab_sections')->nullOnDelete();
            $table->string('code');
            $table->string('name');
            // low|normal|high
            $table->string('level')->default('normal');
            $table->string('lot_number')->nullable();
            $table->date('expiry_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'branch_id', 'code']);
        });

        Schema::create('lab_qc_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('qc_material_id')->constrained('lab_qc_materials')->cascadeOnDelete();
            $table->foreignId('test_id')->constrained('lab_tests')->cascadeOnDelete();
            $table->foreignId('analyzer_id')->nullable()->constrained('lab_analyzers')->nullOnDelete();
            $table->timestamp('run_at');
            $table->decimal('expected_low', 18, 4)->nullable();
            $table->decimal('expected_high', 18, 4)->nullable();
            $table->decimal('observed_value', 18, 4)->nullable();
            // pending|pass|fail
            $table->string('status')->default('pending');
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'branch_id', 'status']);
            $table->index(['qc_material_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_qc_runs');
        Schema::dropIfExists('lab_qc_materials');
    }
};
