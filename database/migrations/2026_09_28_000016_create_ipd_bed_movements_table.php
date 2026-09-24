<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ipd_bed_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('admission_id')->constrained('ipd_admissions')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_bed_id')->nullable()->constrained('ipd_beds')->nullOnDelete();
            $table->foreignId('to_bed_id')->nullable()->constrained('ipd_beds')->nullOnDelete();
            $table->string('movement_type');
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('moved_at')->nullable();
            $table->text('reason')->nullable();
            $table->string('status')->default('requested');
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['admission_id', 'movement_type']);
            $table->index(['company_id', 'branch_id', 'status']);
            $table->index('from_bed_id');
            $table->index('to_bed_id');
            $table->index('moved_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ipd_bed_movements');
    }
};
