<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('providers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->cascadeOnDelete();
            // Nullable: a provider need not be a system login (spec: "do not assume every
            // provider is a normal system user"). When present, this is the source of truth for
            // name/contact; scheduling code should still keep working against provider_id alone.
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('provider_code')->nullable();
            $table->string('name');
            $table->string('provider_type');
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            // Plain column, no FK yet — the specialties table doesn't exist this early in the
            // migration history (it's created by the Clinical module later on). The constraint
            // itself is added by add_specialty_foreign_keys_to_appointment_tables once that table
            // exists.
            $table->unsignedBigInteger('specialty_id')->nullable();
            $table->string('license_number')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'provider_code'], 'provider_company_code_unique');
            $table->index(['company_id', 'branch_id', 'status'], 'provider_company_branch_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('providers');
    }
};
