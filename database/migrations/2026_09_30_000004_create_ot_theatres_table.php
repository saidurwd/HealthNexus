<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ot_theatres', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('complex_id')->nullable()->constrained('ot_complexes')->nullOnDelete();
            $table->string('theatre_code');
            $table->string('name');
            $table->string('location')->nullable();
            $table->unsignedInteger('capacity')->default(1);
            $table->boolean('emergency_capable')->default(false);
            $table->string('status')->default('available');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['organization_id', 'theatre_code']);
            $table->index(['organization_id', 'branch_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ot_theatres');
    }
};
