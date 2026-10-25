<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ot_surgical_complications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surgery_id')->constrained('ot_surgeries')->cascadeOnDelete();
            $table->string('complication');
            $table->string('severity')->default('moderate');
            $table->string('phase')->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->text('description')->nullable();
            $table->text('action_taken')->nullable();
            $table->text('outcome')->nullable();
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('surgery_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ot_surgical_complications');
    }
};
