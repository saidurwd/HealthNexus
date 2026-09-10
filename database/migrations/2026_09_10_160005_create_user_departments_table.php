<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->enum('access_level', ['head', 'staff'])->default('staff');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'department_id']);
            $table->index(['user_id', 'company_id']);
            $table->index(['department_id', 'access_level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_departments');
    }
};
