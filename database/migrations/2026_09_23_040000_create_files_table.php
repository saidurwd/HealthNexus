<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->string('disk')->default('local');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type');
            $table->string('extension', 32);
            $table->unsignedBigInteger('size')->default(0);
            $table->string('checksum')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('entity_type')->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->boolean('is_public')->default(false);
            $table->string('hash')->nullable();
            $table->timestamps();

            $table->unique(['disk', 'path']);
            $table->index(['uploaded_by']);
            $table->index(['entity_type', 'entity_id']);
            $table->index('created_at');
        });

        Schema::create('file_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('file_id')->constrained()->cascadeOnDelete();
            $table->string('disk')->default('local');
            $table->string('path');
            $table->unsignedBigInteger('size')->default(0);
            $table->string('checksum')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('file_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('file_versions');
        Schema::dropIfExists('files');
    }
};
