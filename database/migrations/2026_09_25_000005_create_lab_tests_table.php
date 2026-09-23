<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('lab_test_categories')->nullOnDelete();
            $table->foreignId('section_id')->nullable()->constrained('lab_sections')->nullOnDelete();
            $table->foreignId('specimen_type_id')->nullable()->constrained('lab_specimen_types')->nullOnDelete();
            $table->foreignId('container_type_id')->nullable()->constrained('lab_container_types')->nullOnDelete();
            $table->string('code');
            $table->string('name');
            $table->string('short_name')->nullable();
            $table->text('description')->nullable();
            // quantitative|qualitative|semi_quantitative|text|categorical|calculated|microbiology|culture|pathology
            $table->string('test_type')->default('quantitative');
            $table->string('method')->nullable();
            $table->string('unit')->nullable();
            $table->boolean('fasting_required')->default(false);
            $table->unsignedInteger('turnaround_time_minutes')->nullable();
            $table->boolean('is_panel')->default(false);
            $table->boolean('requires_pathologist_approval')->default(false);
            $table->boolean('is_active')->default(true);
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'branch_id', 'code']);
            $table->index(['company_id', 'branch_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_tests');
    }
};
