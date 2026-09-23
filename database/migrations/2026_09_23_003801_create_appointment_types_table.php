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
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->string('color', 20)->nullable();
            $table->boolean('is_follow_up_type')->default(false);
            $table->boolean('is_telemedicine_type')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            // company_id nullable = a system-wide default type available to every company;
            // a non-null company_id lets a hospital add its own custom type.
            $table->unique(['company_id', 'code'], 'appointment_type_company_code_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_types');
    }
};
