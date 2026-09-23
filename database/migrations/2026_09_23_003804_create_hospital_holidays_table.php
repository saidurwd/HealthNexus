<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hospital_holidays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            // Null branch/department = applies hospital-wide; a holiday is never assumed from a
            // national calendar automatically, it must be entered explicitly (per spec).
            $table->foreignId('branch_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->date('date');
            $table->boolean('is_recurring_annually')->default(false);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'branch_id', 'date'], 'hospital_holiday_branch_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hospital_holidays');
    }
};
