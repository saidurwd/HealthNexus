<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('radiology_series', function (Blueprint $table) {
            $table->id();
            $table->foreignId('study_id')->constrained('radiology_studies')->cascadeOnDelete();
            $table->string('series_instance_uid');
            $table->string('series_number')->nullable();
            $table->string('series_description')->nullable();
            $table->string('modality')->nullable();
            $table->string('body_part')->nullable();
            $table->unsignedInteger('number_of_instances')->default(0);
            $table->timestamps();

            $table->unique('series_instance_uid');
            $table->index(['study_id']);
        });

        Schema::create('radiology_instances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('series_id')->constrained('radiology_series')->cascadeOnDelete();
            $table->string('sop_instance_uid');
            $table->string('sop_class_uid')->nullable();
            $table->string('instance_number')->nullable();
            // A PACS-side reference (e.g. WADO-RS path), never a local file path — no DICOM
            // binary is stored in this application (plan decision #3).
            $table->string('file_reference')->nullable();
            $table->timestamps();

            $table->unique('sop_instance_uid');
            $table->index(['series_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('radiology_instances');
        Schema::dropIfExists('radiology_series');
    }
};
