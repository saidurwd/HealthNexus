<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('radiology_protocols', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procedure_id')->constrained('radiology_procedures')->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('contrast_required')->nullable();
            $table->text('preparation_instructions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['procedure_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('radiology_protocols');
    }
};
