<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('preferred_language')->nullable();
            $table->enum('preferred_contact_method', ['phone', 'email', 'sms'])->nullable();
            $table->enum('preferred_notification_channel', ['sms', 'email', 'push', 'none'])->default('none');
            $table->text('accessibility_requirements')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_preferences');
    }
};
