<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacy_medications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('generic_id')->nullable()->constrained('pharmacy_generics')->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained('pharmacy_brands')->nullOnDelete();
            $table->foreignId('dosage_form_id')->nullable()->constrained('pharmacy_dosage_forms')->nullOnDelete();
            $table->foreignId('route_id')->nullable()->constrained('pharmacy_routes')->nullOnDelete();
            $table->string('code');
            $table->string('name');
            $table->string('strength')->nullable();
            $table->string('strength_unit')->nullable();
            $table->unsignedInteger('pack_size')->nullable();
            $table->string('dispensing_unit')->nullable();
            $table->string('prescription_unit')->nullable();
            $table->string('manufacturer')->nullable();
            $table->boolean('is_prescription_required')->default(true);
            $table->boolean('is_controlled')->default(false);
            $table->boolean('is_high_alert')->default(false);
            $table->decimal('storage_temperature_min', 5, 2)->nullable();
            $table->decimal('storage_temperature_max', 5, 2)->nullable();
            $table->boolean('temperature_sensitive')->default(false);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'branch_id', 'code']);
            $table->index(['company_id', 'branch_id', 'is_active']);
            $table->index('name');
            $table->index('is_controlled');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_medications');
    }
};
