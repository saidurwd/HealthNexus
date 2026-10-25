<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Append-only scheduling ledger — replaces the spec's separate schedule-history/
     * cancellations/postponements/delay-records tables with one history row per change
     * (decision #13). Never deleted or updated in place.
     */
    public function up(): void
    {
        Schema::create('ot_surgery_schedule_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surgery_id')->constrained('ot_surgeries')->cascadeOnDelete();
            $table->foreignId('theatre_id')->nullable()->constrained('ot_theatres')->nullOnDelete();
            $table->string('change_type');
            $table->date('scheduled_date')->nullable();
            $table->timestamp('scheduled_start_at')->nullable();
            $table->timestamp('scheduled_end_at')->nullable();
            $table->string('reason')->nullable();
            $table->string('delay_category')->nullable();
            $table->unsignedInteger('delay_minutes')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('changed_at');
            $table->timestamps();

            $table->index(['surgery_id', 'change_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ot_surgery_schedule_events');
    }
};
