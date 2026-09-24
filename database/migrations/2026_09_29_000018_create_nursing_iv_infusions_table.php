<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nursing_iv_infusions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('episode_id')->constrained('nursing_episodes')->cascadeOnDelete();
            $table->foreignId('admission_id')->constrained('ipd_admissions')->cascadeOnDelete();
            $table->foreignId('prescription_item_id')->nullable()->constrained('prescription_items')->nullOnDelete();
            $table->foreignId('device_id')->nullable()->constrained('nursing_devices')->nullOnDelete();
            $table->string('fluid_name');
            $table->string('rate')->nullable();
            $table->timestamp('start_time');
            $table->timestamp('expected_completion')->nullable();
            $table->timestamp('actual_completion')->nullable();
            $table->string('site')->nullable();
            $table->string('volume')->nullable();
            $table->string('complications')->nullable();
            $table->string('status')->default('running');
            $table->foreignId('monitored_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['episode_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nursing_iv_infusions');
    }
};
