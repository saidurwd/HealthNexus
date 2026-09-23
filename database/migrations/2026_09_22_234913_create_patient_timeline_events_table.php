<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_timeline_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('event_type');
            $table->string('description');
            $table->nullableMorphs('subject');
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamp('event_at');
            $table->timestamps();

            $table->index(['company_id', 'patient_id', 'event_at'], 'patient_timeline_patient_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_timeline_events');
    }
};
