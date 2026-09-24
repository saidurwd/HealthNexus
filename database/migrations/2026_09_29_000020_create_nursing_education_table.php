<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nursing_education', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('episode_id')->constrained('nursing_episodes')->cascadeOnDelete();
            $table->foreignId('admission_id')->constrained('ipd_admissions')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('topic');
            $table->text('education_provided');
            $table->string('method')->nullable();
            $table->string('patient_understanding')->nullable();
            $table->boolean('caregiver_involvement')->default(false);
            $table->string('materials')->nullable();
            $table->foreignId('provided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('provided_at');
            $table->timestamps();

            $table->index(['episode_id', 'provided_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nursing_education');
    }
};
