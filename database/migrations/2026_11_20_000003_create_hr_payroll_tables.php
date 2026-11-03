<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Phase 16, part 3: pay components, salary structures, loans, dependents, tax slabs, payroll periods and runs, payment batches, appraisals, training (S08–S11). Money is DECIMAL(14,2). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_pay_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('code', 20);
            $table->string('name', 100);
            $table->string('kind', 12); // earning | deduction | employer
            $table->string('method', 16)->default('fixed'); // fixed | percent_basic | percent_gross
            $table->boolean('is_basic')->default(false);
            $table->boolean('taxable')->default(true);
            $table->boolean('prorate')->default(true); // reduced for days not paid
            $table->string('status', 10)->default('active');
            $table->timestamps();

            $table->unique(['organization_id', 'code']);
        });

        // A structure is a version: it applies from one date until the next version of the same employee starts. Old versions are never edited.
        Schema::create('hr_salary_structures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->unsignedSmallInteger('version')->default(1);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->string('reason', 200)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['employee_id', 'version'], 'uq_hr_structure_version');
            $table->index(['employee_id', 'effective_from'], 'idx_hr_structure_date');
        });

        Schema::create('hr_salary_structure_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('structure_id')->constrained('hr_salary_structures')->cascadeOnDelete();
            $table->foreignId('component_id')->constrained('hr_pay_components')->restrictOnDelete();
            $table->decimal('amount', 14, 2)->nullable();   // for method fixed
            $table->decimal('percent', 7, 4)->nullable();   // for the percent methods
            $table->timestamps();
        });

        Schema::create('hr_loans', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->string('kind', 10)->default('loan'); // loan | advance
            $table->decimal('amount', 14, 2);
            $table->decimal('monthly_deduction', 14, 2);
            $table->decimal('balance', 14, 2);
            $table->date('start_month');
            $table->string('status', 10)->default('requested'); // requested | active | repaid | cancelled
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->string('reason', 200)->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'status']);
        });

        Schema::create('hr_loan_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained('hr_loans')->cascadeOnDelete();
            $table->decimal('amount', 14, 2); // signed: + disbursed, - repaid
            $table->string('entry_type', 12); // disbursement | deduction | writeoff
            $table->unsignedBigInteger('payroll_run_id')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('hr_dependents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('relation', 20);
            $table->date('date_of_birth')->nullable();
            $table->timestamps();
        });

        // Tax slabs are settings with an effective date (guideline override 2): no rate lives in code.
        Schema::create('hr_tax_slabs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->date('effective_from');
            $table->decimal('from_amount', 14, 2);  // annual taxable income from (inclusive)
            $table->decimal('to_amount', 14, 2)->nullable(); // up to; null = no upper limit
            $table->decimal('rate_percent', 6, 3);
            $table->timestamps();

            $table->index(['organization_id', 'effective_from']);
        });

        Schema::create('hr_payroll_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->date('month_start');
            $table->date('month_end');
            $table->string('status', 10)->default('open'); // open | processing | closed
            $table->timestamps();

            $table->unique(['organization_id', 'month_start'], 'uq_hr_payroll_period');
        });

        Schema::create('hr_payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('period_id')->constrained('hr_payroll_periods')->restrictOnDelete();
            $table->string('number', 30);
            $table->string('run_type', 13)->default('regular'); // regular | supplementary
            $table->unsignedSmallInteger('calculation')->default(0); // how many times it has been calculated while still a draft
            $table->string('status', 12)->default('draft'); // draft | calculated | approved | paid | cancelled
            $table->unsignedSmallInteger('employee_count')->default(0);
            $table->decimal('total_gross', 14, 2)->default(0);
            $table->decimal('total_deductions', 14, 2)->default(0);
            $table->decimal('total_tax', 14, 2)->default(0);
            $table->decimal('total_net', 14, 2)->default(0);
            $table->decimal('total_employer', 14, 2)->default(0);
            $table->string('input_hash', 64)->nullable(); // fingerprint of everything the calculation read: same inputs, same hash, same result
            $table->string('reason', 300)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('payslips_published_at')->nullable();
            $table->json('journal')->nullable();
            $table->string('finance_reference', 80)->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'number']);
            $table->index(['period_id', 'run_type']);
        });

        Schema::create('hr_payroll_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('run_id')->constrained('hr_payroll_runs')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('hr_employees')->restrictOnDelete();
            $table->decimal('gross', 14, 2)->default(0);
            $table->decimal('deductions', 14, 2)->default(0);
            $table->decimal('tax', 14, 2)->default(0);
            $table->decimal('net', 14, 2)->default(0);
            $table->decimal('employer', 14, 2)->default(0);
            $table->json('inputs')->nullable(); // days, unpaid days, overtime minutes, structure version: what the numbers came from
            $table->json('exceptions')->nullable();
            $table->timestamps();

            $table->unique(['run_id', 'employee_id'], 'uq_hr_payroll_line');
        });

        Schema::create('hr_payroll_line_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('line_id')->constrained('hr_payroll_lines')->cascadeOnDelete();
            $table->string('code', 20);
            $table->string('name', 100);
            $table->string('kind', 12); // earning | deduction | tax | employer
            $table->decimal('amount', 14, 2);
            $table->string('note', 200)->nullable();
        });

        // Corrections to a closed month: entered here, paid through a supplementary run.
        Schema::create('hr_payroll_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->foreignId('period_id')->constrained('hr_payroll_periods')->restrictOnDelete(); // the month being corrected
            $table->string('kind', 10); // earning | deduction
            $table->string('name', 100);
            $table->decimal('amount', 14, 2);
            $table->string('reason', 300);
            $table->unsignedBigInteger('run_id')->nullable(); // the supplementary run that paid it
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('hr_payment_batches', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('run_id')->constrained('hr_payroll_runs')->restrictOnDelete();
            $table->string('method', 14); // bank | mobile_wallet
            $table->unsignedSmallInteger('payee_count');
            $table->decimal('total', 14, 2);
            $table->string('file_hash', 64);
            $table->string('status', 10)->default('prepared'); // prepared | sent | confirmed
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['run_id', 'method'], 'uq_hr_payment_batch');
        });

        Schema::create('hr_appraisal_cycles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->date('period_start');
            $table->date('period_end');
            $table->string('status', 8)->default('open'); // open | closed
            $table->timestamps();
        });

        Schema::create('hr_appraisals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cycle_id')->constrained('hr_appraisal_cycles')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->foreignId('reviewer_id')->nullable()->constrained('hr_employees')->nullOnDelete();
            $table->unsignedTinyInteger('rating')->nullable(); // 1–5
            $table->string('strengths', 400)->nullable();
            $table->string('improvements', 400)->nullable();
            $table->string('status', 14)->default('pending'); // pending | submitted | acknowledged
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamps();

            $table->unique(['cycle_id', 'employee_id'], 'uq_hr_appraisal');
        });

        Schema::create('hr_trainings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('title', 150);
            $table->date('held_on');
            $table->boolean('mandatory')->default(false);
            $table->unsignedSmallInteger('valid_months')->nullable(); // how long the training counts
            $table->timestamps();
        });

        Schema::create('hr_training_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('training_id')->constrained('hr_trainings')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->string('outcome', 10)->default('attended'); // attended | passed | failed | absent
            $table->date('valid_until')->nullable();
            $table->timestamps();

            $table->unique(['training_id', 'employee_id'], 'uq_hr_training_record');
        });
    }

    public function down(): void
    {
        foreach (['hr_training_records', 'hr_trainings', 'hr_appraisals', 'hr_appraisal_cycles', 'hr_payment_batches', 'hr_payroll_adjustments', 'hr_payroll_line_items', 'hr_payroll_lines', 'hr_payroll_runs', 'hr_payroll_periods', 'hr_tax_slabs', 'hr_dependents', 'hr_loan_entries', 'hr_loans', 'hr_salary_structure_lines', 'hr_salary_structures', 'hr_pay_components'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
