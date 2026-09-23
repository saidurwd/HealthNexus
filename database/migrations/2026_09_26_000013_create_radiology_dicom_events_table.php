<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lightweight append-only observability log for PACS/DICOM sync attempts (spec §93) — also the
 * reserved home for future Modality Worklist / MPPS foundation events (spec §65/§66), not a
 * working protocol implementation.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('radiology_dicom_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('radiology_examination_id')->nullable()->constrained('radiology_examinations')->nullOnDelete();
            $table->foreignId('pacs_server_id')->nullable()->constrained('radiology_pacs_servers')->nullOnDelete();
            // study_sync|mwl_foundation|mpps_foundation
            $table->string('event_type');
            // success|failed|skipped
            $table->string('status');
            $table->text('summary')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'branch_id', 'event_type', 'status'], 'radiology_dicom_events_scope_idx');
            $table->index(['radiology_examination_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('radiology_dicom_events');
    }
};
