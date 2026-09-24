<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nursing_device_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained('nursing_devices')->cascadeOnDelete();
            $table->timestamp('assessed_at');
            $table->foreignId('assessed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('findings');
            $table->text('action_taken')->nullable();
            $table->timestamps();

            $table->index(['device_id', 'assessed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nursing_device_assessments');
    }
};
