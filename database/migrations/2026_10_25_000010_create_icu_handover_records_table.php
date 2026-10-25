<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('icu_handover_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('episode_id')->constrained('icu_episodes')->cascadeOnDelete();
            $table->foreignId('outgoing_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('incoming_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('handover_type')->default('nurse');
            $table->json('sections')->nullable();
            $table->string('status')->default('draft');
            $table->timestamp('prepared_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamps();

            $table->index(['episode_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('icu_handover_records');
    }
};
