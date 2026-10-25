<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->string('color', 20)->nullable();
            $table->boolean('is_follow_up_type')->default(false);
            $table->boolean('is_telemedicine_type')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            // organization_id nullable = a system-wide default type available to every organization;
            // a non-null organization_id lets a hospital add its own custom type.
            $table->unique(['organization_id', 'code'], 'appointment_type_organization_code_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_types');
    }
};
