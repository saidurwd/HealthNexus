<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_allergies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('substance');
            $table->enum('severity', ['mild', 'moderate', 'severe'])->nullable();
            $table->enum('reaction', ['rash', 'hives', 'itching', 'swelling', 'anaphylaxis', 'other'])->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'patient_id']);
            $table->index(['company_id', 'substance']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_allergies');
    }
};
