<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Surgical specialty reuses the existing organization-wide `specialties` table (already used by
     * Provider.specialty_id and IpdAdmission.specialty_id) — no ot_specialties table.
     */
    public function up(): void
    {
        Schema::create('ot_procedures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('ot_procedure_categories')->nullOnDelete();
            $table->foreignId('specialty_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code');
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('default_duration_minutes')->default(60);
            $table->string('anesthesia_category')->nullable();
            $table->boolean('laterality_applicable')->default(false);
            $table->boolean('site_applicable')->default(false);
            $table->boolean('day_care_eligible')->default(false);
            $table->boolean('emergency_eligible')->default(true);
            $table->boolean('requires_implant')->default(false);
            $table->boolean('requires_blood')->default(false);
            $table->boolean('requires_specimen')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['organization_id', 'branch_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ot_procedures');
    }
};
