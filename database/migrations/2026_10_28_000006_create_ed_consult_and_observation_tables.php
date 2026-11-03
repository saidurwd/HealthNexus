<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ed_consults', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('visit_id')->constrained('ed_visits')->cascadeOnDelete();
            $table->string('specialty', 100);
            $table->string('urgency', 20);
            $table->string('reason', 500)->nullable();
            $table->string('status', 20)->default('requested'); // requested | responded | cancelled
            $table->timestamp('requested_at');
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('due_at');
            $table->timestamp('responded_at')->nullable();
            $table->foreignId('responded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('response_note', 1000)->nullable();
            $table->timestamps();

            $table->index(['status', 'due_at']);
            $table->index('visit_id');
        });

        Schema::create('ed_observation_stays', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('visit_id')->constrained('ed_visits')->cascadeOnDelete();
            $table->timestamp('started_at');
            $table->foreignId('started_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('ended_at')->nullable();
            $table->foreignId('ended_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedSmallInteger('billed_hours')->nullable();
            $table->timestamps();

            $table->index(['visit_id', 'ended_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ed_observation_stays');
        Schema::dropIfExists('ed_consults');
    }
};
