<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ipd_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('ward_id')->constrained('ipd_wards')->cascadeOnDelete();
            $table->string('room_number');
            $table->string('room_type')->default('general');
            $table->unsignedInteger('capacity')->default(1);
            $table->string('gender_policy')->default('any');
            $table->boolean('isolation_capable')->default(false);
            $table->boolean('is_vip')->default(false);
            $table->string('rate_category')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['ward_id', 'room_number']);
            $table->index(['company_id', 'branch_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ipd_rooms');
    }
};
