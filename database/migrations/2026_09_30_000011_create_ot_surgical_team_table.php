<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Append-only team assignment history (spec §17) — mirrors ipd_provider_assignments exactly.
     */
    public function up(): void
    {
        Schema::create('ot_surgical_team', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surgery_id')->constrained('ot_surgeries')->cascadeOnDelete();
            $table->foreignId('provider_id')->constrained('providers')->cascadeOnDelete();
            $table->string('role');
            $table->timestamp('assigned_at');
            $table->timestamp('removed_at')->nullable();
            $table->string('reason')->nullable();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['surgery_id', 'role']);
            $table->index(['surgery_id', 'removed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ot_surgical_team');
    }
};
