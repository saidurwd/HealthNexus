<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Append-only: events are added, never edited. A correction is a new event. No vital-sign values live here (they are Observations).
        Schema::create('ed_resus_events', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('visit_id')->constrained('ed_visits')->cascadeOnDelete();
            $table->string('event_type', 40);
            $table->string('detail', 500)->nullable();
            $table->timestamp('occurred_at');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['visit_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ed_resus_events');
    }
};
