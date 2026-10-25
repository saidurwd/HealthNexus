<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacy_stock_counts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('store_id')->constrained('pharmacy_stores')->cascadeOnDelete();
            $table->string('count_number');
            $table->string('status')->default('in_progress');
            $table->foreignId('started_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'count_number']);
            $table->index(['organization_id', 'branch_id', 'status']);
            $table->index('store_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_stock_counts');
    }
};
