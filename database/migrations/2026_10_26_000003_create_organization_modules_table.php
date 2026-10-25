<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A row overrides the registry default. hospital_id NULL = organization-wide;
        // a hospital row overrides the organization row for that hospital only.
        Schema::create('organization_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('hospital_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('module_key', 40);
            $table->boolean('is_enabled');
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['organization_id', 'hospital_id', 'module_key'], 'uq_org_modules_scope_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_modules');
    }
};
