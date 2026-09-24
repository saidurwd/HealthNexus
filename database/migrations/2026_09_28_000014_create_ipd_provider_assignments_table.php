<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ipd_provider_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admission_id')->constrained('ipd_admissions')->cascadeOnDelete();
            $table->foreignId('provider_id')->constrained('providers')->cascadeOnDelete();
            $table->string('role');
            $table->timestamp('assigned_at');
            $table->timestamp('ended_at')->nullable();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['admission_id', 'role']);
            $table->index(['admission_id', 'ended_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ipd_provider_assignments');
    }
};
