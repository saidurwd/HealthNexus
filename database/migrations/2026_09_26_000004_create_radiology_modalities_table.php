<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('radiology_modalities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('section_id')->nullable()->constrained('radiology_sections')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            // AppointmentRoom representing this modality's physical bay — used for scheduling
            // conflict checks (Phase 6 plan decision #1), never a new parallel room concept.
            $table->foreignId('room_id')->nullable()->constrained('appointment_rooms')->nullOnDelete();
            $table->string('code');
            $table->string('name');
            // CT|MRI|XR|US|Mammography|Fluoroscopy|Angiography|PET|PET-CT|SPECT|Dental XR|DEXA|...
            // Free-string, configurable — never a hardcoded enum (spec §10).
            $table->string('modality_type');
            $table->string('manufacturer')->nullable();
            $table->string('model')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('ae_title')->nullable();
            $table->string('ip_address')->nullable();
            $table->unsignedInteger('port')->nullable();
            $table->string('pacs_endpoint')->nullable();
            $table->string('location')->nullable();
            // online|offline|maintenance|disabled
            $table->string('status')->default('offline');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'branch_id', 'code']);
            $table->index(['company_id', 'branch_id', 'is_active']);
            $table->index(['modality_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('radiology_modalities');
    }
};
