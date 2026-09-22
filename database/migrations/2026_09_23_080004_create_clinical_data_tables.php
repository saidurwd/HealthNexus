<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('encounter_complaints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('encounter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('complaint');
            $table->string('duration')->nullable();
            $table->string('duration_unit')->nullable();
            $table->string('onset')->nullable();
            $table->string('severity')->nullable();
            $table->string('location')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['encounter_id', 'sort_order']);
        });

        Schema::create('encounter_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('encounter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('history_type');
            $table->string('onset')->nullable();
            $table->string('duration')->nullable();
            $table->string('course')->nullable();
            $table->string('severity')->nullable();
            $table->text('associated_symptoms')->nullable();
            $table->text('aggravating_factors')->nullable();
            $table->text('relieving_factors')->nullable();
            $table->text('clinical_notes')->nullable();
            $table->timestamps();

            $table->index(['encounter_id']);
        });

        Schema::create('encounter_examinations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('encounter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('section_name');
            $table->text('findings')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['encounter_id']);
        });

        Schema::create('encounter_review_of_systems', function (Blueprint $table) {
            $table->id();
            $table->foreignId('encounter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('system_name');
            $table->string('status');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['encounter_id']);
        });

        Schema::create('patient_problems', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('problem_code')->nullable();
            $table->string('problem_name');
            $table->string('coding_system')->nullable();
            $table->string('status')->default('active');
            $table->date('onset_date')->nullable();
            $table->date('resolved_date')->nullable();
            $table->foreignId('source_encounter_id')->nullable()->constrained('encounters')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['patient_id', 'status']);
        });

        Schema::create('encounter_procedures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('encounter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('procedure_code')->nullable();
            $table->string('procedure_name');
            $table->date('procedure_date')->nullable();
            $table->foreignId('provider_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->string('status')->default('completed');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('recorded_at')->nullable();
            $table->timestamps();

            $table->index(['encounter_id']);
        });

        Schema::create('clinical_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('order_number')->unique();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('encounter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('provider_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('order_type');
            $table->string('priority')->default('routine');
            $table->string('status')->default('requested');
            $table->timestamp('ordered_at')->nullable();
            $table->foreignId('ordered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['encounter_id']);
        });

        Schema::create('clinical_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinical_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('item_name');
            $table->string('item_code')->nullable();
            $table->unsignedInteger('quantity')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        Schema::create('encounter_referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('encounter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('referral_type');
            $table->string('referred_to')->nullable();
            $table->string('referred_by')->nullable();
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('pending');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['encounter_id']);
        });

        Schema::create('encounter_instructions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('encounter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('instruction_type');
            $table->text('content');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['encounter_id']);
        });

        Schema::create('encounter_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('encounter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('section')->nullable();
            $table->text('content');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['encounter_id']);
        });

        Schema::create('encounter_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('encounter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('document_type');
            $table->string('file_name');
            $table->string('file_path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['encounter_id']);
        });

        Schema::create('encounter_amendments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('encounter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('amendment_type');
            $table->text('reason');
            $table->text('content');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['encounter_id']);
        });

        Schema::create('encounter_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('specialty')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'specialty']);
        });

        Schema::create('encounter_template_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('encounter_template_id')->constrained()->cascadeOnDelete();
            $table->string('section_name');
            $table->string('section_key');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_required')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('encounter_template_sections');
        Schema::dropIfExists('encounter_templates');
        Schema::dropIfExists('encounter_amendments');
        Schema::dropIfExists('encounter_documents');
        Schema::dropIfExists('encounter_notes');
        Schema::dropIfExists('encounter_instructions');
        Schema::dropIfExists('encounter_referrals');
        Schema::dropIfExists('clinical_order_items');
        Schema::dropIfExists('clinical_orders');
        Schema::dropIfExists('encounter_procedures');
        Schema::dropIfExists('patient_problems');
        Schema::dropIfExists('encounter_review_of_systems');
        Schema::dropIfExists('encounter_examinations');
        Schema::dropIfExists('encounter_histories');
        Schema::dropIfExists('encounter_complaints');
    }
};
