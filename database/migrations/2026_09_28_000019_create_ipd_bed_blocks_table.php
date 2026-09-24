<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ipd_bed_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('bed_id')->constrained('ipd_beds')->cascadeOnDelete();
            $table->string('reason_type');
            $table->text('reason')->nullable();
            $table->timestamp('start_at');
            $table->timestamp('expected_end_at')->nullable();
            $table->timestamp('actual_end_at')->nullable();
            $table->string('status')->default('active');
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'branch_id', 'status']);
            $table->index(['bed_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ipd_bed_blocks');
    }
};
