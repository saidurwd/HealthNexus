<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nursing_handover', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('episode_id')->constrained('nursing_episodes')->cascadeOnDelete();
            $table->foreignId('admission_id')->constrained('ipd_admissions')->cascadeOnDelete();
            $table->foreignId('outgoing_nurse_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('incoming_nurse_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('shift_id')->nullable()->constrained('nursing_shifts')->nullOnDelete();
            $table->string('status')->default('draft');
            $table->timestamp('prepared_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamps();

            $table->index(['episode_id', 'status']);
            $table->index(['incoming_nurse_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nursing_handover');
    }
};
