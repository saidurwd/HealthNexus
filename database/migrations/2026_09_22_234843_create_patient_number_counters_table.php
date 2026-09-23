<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_number_counters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->cascadeOnDelete();
            $table->enum('counter_type', ['enterprise', 'local']);
            $table->unsignedBigInteger('last_number')->default(0);
            $table->timestamps();

            $table->unique(['company_id', 'branch_id', 'counter_type'], 'patient_number_counter_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_number_counters');
    }
};
