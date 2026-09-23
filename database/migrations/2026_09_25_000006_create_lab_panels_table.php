<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_panels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code');
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'branch_id', 'code']);
            $table->index(['company_id', 'branch_id', 'is_active']);
        });

        Schema::create('lab_panel_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('panel_id')->constrained('lab_panels')->cascadeOnDelete();
            $table->foreignId('test_id')->constrained('lab_tests')->cascadeOnDelete();
            $table->unsignedInteger('sequence')->default(0);
            $table->timestamps();

            $table->unique(['panel_id', 'test_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_panel_items');
        Schema::dropIfExists('lab_panels');
    }
};
