<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Configuration only — not pharmacy/contrast inventory (spec §16). RIS records administration
 * against an examination; stock/quantity ownership stays with a future Inventory/Pharmacy module.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('radiology_contrast_agents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code');
            $table->string('name');
            $table->string('type')->nullable();
            $table->string('concentration')->nullable();
            $table->string('default_route')->nullable();
            $table->string('default_dose')->nullable();
            $table->string('max_dose')->nullable();
            $table->string('manufacturer')->nullable();
            $table->text('safety_information')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'branch_id', 'code']);
            $table->index(['organization_id', 'branch_id', 'is_active'], 'radiology_contrast_agents_63255855_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('radiology_contrast_agents');
    }
};
