<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // History of backups and restore drills (guideline 21.4: "a backup that was never restored is not a backup").
        Schema::create('backup_runs', function (Blueprint $table) {
            $table->id();
            $table->string('type', 16);                  // backup, restore_drill
            $table->string('status', 12);                // running, succeeded, failed
            $table->string('disk', 30)->nullable();
            $table->string('path')->nullable();
            $table->boolean('encrypted')->default(false);
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->string('checksum', 64)->nullable();
            $table->unsignedBigInteger('source_run_id')->nullable();   // the backup a drill restored
            $table->json('details')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->string('signed_off_by')->nullable();   // the human who accepted the drill in the runbook
            $table->timestamp('signed_off_at')->nullable();

            $table->index(['type', 'status', 'finished_at'], 'idx_backup_runs_latest');
        });

        // Last downtime census produced per ward (read-only copy kept for use when the system is down).
        Schema::create('downtime_censuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('disk', 30);
            $table->string('path');
            $table->unsignedInteger('patient_count');
            $table->timestamp('generated_at');

            $table->index(['organization_id', 'generated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('downtime_censuses');
        Schema::dropIfExists('backup_runs');
    }
};
