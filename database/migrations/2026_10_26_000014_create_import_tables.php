<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Staged import (guideline H-11): upload → validate → preview → commit → error file → rollback of a whole batch. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_batches', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('import_key', 40);
            $table->string('original_name');
            $table->string('status', 16)->default('uploaded');   // uploaded, validating, validated, committing, committed, rolled_back, failed
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('valid_rows')->default(0);
            $table->unsignedInteger('error_rows')->default(0);
            $table->unsignedInteger('duplicate_rows')->default(0);
            $table->unsignedInteger('imported_rows')->default(0);
            $table->json('header')->nullable();
            $table->json('options')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->timestamp('committed_at')->nullable();
            $table->timestamp('rolled_back_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'import_key', 'status'], 'idx_import_batches_scope');
        });

        Schema::create('import_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_batch_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('row_number');
            $table->json('raw');
            $table->json('data')->nullable();
            $table->string('status', 12)->default('pending');   // pending, valid, error, duplicate, imported, rolled_back, kept
            $table->json('errors')->nullable();
            $table->string('duplicate_of')->nullable();
            $table->string('target_type', 100)->nullable();
            $table->unsignedBigInteger('target_id')->nullable();
            $table->string('note')->nullable();

            $table->index(['import_batch_id', 'status'], 'idx_import_rows_status');
            $table->unique(['import_batch_id', 'row_number'], 'uq_import_rows_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_rows');
        Schema::dropIfExists('import_batches');
    }
};
