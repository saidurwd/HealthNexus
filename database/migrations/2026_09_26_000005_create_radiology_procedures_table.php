<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('radiology_procedures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('section_id')->nullable()->constrained('radiology_sections')->nullOnDelete();
            $table->foreignId('body_part_id')->nullable()->constrained('radiology_body_parts')->nullOnDelete();
            $table->string('code');
            $table->string('name');
            $table->string('modality_type');
            $table->text('description')->nullable();
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->unsignedInteger('turnaround_time_minutes')->nullable();
            $table->boolean('contrast_required')->default(false);
            $table->boolean('preparation_required')->default(false);
            $table->boolean('sedation_required')->default(false);
            $table->text('preparation_instructions')->nullable();
            $table->boolean('requires_senior_approval')->default(false);
            $table->boolean('is_active')->default(true);
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
        Schema::dropIfExists('radiology_procedures');
    }
};
