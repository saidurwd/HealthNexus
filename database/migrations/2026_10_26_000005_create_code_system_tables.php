<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Code System Registry (guideline 17.2, H-13). Class P: platform data, no tenant columns. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('code_systems', function (Blueprint $table) {
            $table->id();
            $table->string('key', 30)->unique();       // short stable key used in code: icd10, icd11, loinc, atc, ucum, snomed, dgda
            $table->string('uri')->unique();
            $table->string('name');
            $table->string('version', 40)->nullable();
            $table->string('publisher')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamp('loaded_at')->nullable();
            $table->timestamps();
        });

        Schema::create('code_concepts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('code_system_id')->constrained()->cascadeOnDelete();
            $table->string('code', 64);
            $table->string('display_en', 500);
            $table->string('display_bn', 500)->nullable();
            $table->string('parent_code', 64)->nullable();
            $table->string('status', 20)->default('active');
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->timestamps();

            $table->unique(['code_system_id', 'code'], 'uq_code_concepts_system_code');
            $table->index(['code_system_id', 'display_en'], 'idx_code_concepts_display');
        });

        Schema::create('code_maps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_system_id')->constrained('code_systems')->cascadeOnDelete();
            $table->string('from_code', 64);
            $table->foreignId('to_system_id')->constrained('code_systems')->cascadeOnDelete();
            $table->string('to_code', 64);
            $table->string('relationship', 20)->default('equivalent'); // equivalent, broader, narrower, related
            $table->timestamps();

            $table->unique(['from_system_id', 'from_code', 'to_system_id', 'to_code'], 'uq_code_maps_pair');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('code_maps');
        Schema::dropIfExists('code_concepts');
        Schema::dropIfExists('code_systems');
    }
};
