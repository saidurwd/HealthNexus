<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('condition');
            $table->text('description')->nullable();
            $table->date('diagnosed_at')->nullable();
            $table->date('resolved_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'patient_id']);
            $table->index(['organization_id', 'condition']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_histories');
    }
};
