<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Report & Export Service (guideline 14.4, H-07): every export is a queued, scoped, audited, expiring job. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_exports', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('report_key', 60);
            $table->string('format', 10);                            // csv (xlsx, pdf when their packages are approved)
            $table->json('filters')->nullable();
            $table->string('status', 12)->default('queued');          // queued, running, completed, failed, expired
            $table->unsignedInteger('row_count')->nullable();
            $table->string('disk', 30)->nullable();
            $table->string('path')->nullable();
            $table->string('checksum', 64)->nullable();
            $table->text('error')->nullable();
            $table->timestamp('requested_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->unsignedSmallInteger('download_count')->default(0);
            $table->timestamp('last_downloaded_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'user_id', 'status'], 'idx_report_exports_user');
            $table->index(['status', 'expires_at'], 'idx_report_exports_expiry');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_exports');
    }
};
