<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_identifiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('identifier_type');
            $table->string('identifier_value');
            $table->string('issuing_authority')->nullable();
            $table->date('issued_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'patient_id', 'identifier_type', 'identifier_value'], 'patient_identifier_unique');
            $table->index(['company_id', 'patient_id', 'identifier_type'], 'patient_identifier_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_identifiers');
    }
};
