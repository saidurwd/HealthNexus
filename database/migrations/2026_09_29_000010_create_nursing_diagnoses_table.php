<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Deliberately distinct from the physician `diagnoses` table (spec §23, stated twice) —
     * nursing diagnoses are never written to or read from that model.
     */
    public function up(): void
    {
        Schema::create('nursing_diagnoses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('care_plan_id')->constrained('nursing_care_plans')->cascadeOnDelete();
            $table->text('diagnosis_text');
            $table->text('related_factors')->nullable();
            $table->text('evidence')->nullable();
            $table->string('priority')->default('medium');
            $table->string('status')->default('active');
            $table->string('coding_system')->nullable();
            $table->string('code')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['care_plan_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nursing_diagnoses');
    }
};
