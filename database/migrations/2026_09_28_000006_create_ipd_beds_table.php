<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ipd_beds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('room_id')->constrained('ipd_rooms')->cascadeOnDelete();
            $table->foreignId('bed_type_id')->nullable()->constrained('ipd_bed_types')->nullOnDelete();
            $table->string('bed_code');
            $table->string('bed_name')->nullable();
            $table->string('gender_type')->default('any');
            $table->string('status')->default('available');
            $table->boolean('isolation_capable')->default(false);
            $table->boolean('icu_capable')->default(false);
            $table->boolean('ventilator_capable')->default(false);
            $table->boolean('oxygen_available')->default(false);
            $table->boolean('monitor_available')->default(false);
            $table->boolean('is_vip')->default(false);
            $table->boolean('is_pediatric')->default(false);
            $table->boolean('is_maternity')->default(false);
            $table->boolean('is_bariatric')->default(false);
            $table->boolean('is_accessible')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'bed_code']);
            $table->index(['company_id', 'branch_id', 'status']);
            $table->index('room_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ipd_beds');
    }
};
