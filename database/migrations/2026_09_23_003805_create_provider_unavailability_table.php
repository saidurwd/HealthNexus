<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_unavailability', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('provider_id')->constrained()->cascadeOnDelete();
            $table->enum('reason_type', ['leave', 'training', 'meeting', 'conference', 'personal', 'emergency', 'other']);
            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'provider_id', 'start_at', 'end_at'], 'provider_unavailability_window_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_unavailability');
    }
};
