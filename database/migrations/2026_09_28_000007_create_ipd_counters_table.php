<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ipd_counters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('document_type', 3);
            $table->string('prefix', 16);
            $table->unsignedBigInteger('last_number')->default(0);
            $table->timestamps();

            $table->unique(['company_id', 'branch_id', 'document_type', 'prefix'], 'ipd_counters_scope_unique');
            $table->index(['company_id', 'branch_id', 'document_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ipd_counters');
    }
};
