<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Phase 16, part 2: shifts and roster, staffing needs, devices and raw punches, attendance days, regularization, leave, overtime (S04–S07). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('code', 20);
            $table->string('name', 80);
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedSmallInteger('break_minutes')->default(0);
            $table->unsignedSmallInteger('grace_minutes')->default(10);
            $table->boolean('is_night')->default(false);
            $table->string('status', 10)->default('active');
            $table->timestamps();

            $table->unique(['organization_id', 'code']);
        });

        Schema::create('hr_roster_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->date('work_date');
            $table->foreignId('shift_id')->constrained('hr_shifts')->restrictOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 10)->default('draft'); // draft | published
            $table->string('note', 200)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['employee_id', 'work_date', 'shift_id'], 'uq_hr_roster_slot');
            $table->index(['organization_id', 'work_date', 'department_id'], 'idx_hr_roster_day');
        });

        Schema::create('hr_staffing_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shift_id')->constrained('hr_shifts')->cascadeOnDelete();
            $table->foreignId('job_id')->nullable()->constrained('hr_jobs')->nullOnDelete();
            $table->unsignedSmallInteger('min_count');
            $table->timestamps();
        });

        Schema::create('hr_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code', 30);
            $table->string('name', 100);
            $table->string('kind', 12)->default('fingerprint'); // fingerprint | face | card | mobile | manual
            $table->timestamp('last_sync_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['organization_id', 'code']);
        });

        // Raw punches are kept exactly as the device sent them; processing reads them and never edits them.
        Schema::create('hr_punches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('device_id')->nullable()->constrained('hr_devices')->nullOnDelete();
            $table->string('device_code', 40);
            $table->unsignedBigInteger('employee_id')->nullable(); // resolved from device_code; unknown codes stay null until someone maps them
            $table->timestamp('punched_at');
            $table->string('source', 10)->default('device'); // device | import | mobile | manual
            $table->string('idempotency_key', 120);
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['organization_id', 'idempotency_key'], 'uq_hr_punch_key');
            $table->index(['organization_id', 'employee_id', 'punched_at'], 'idx_hr_punch_employee');
            $table->index(['organization_id', 'processed_at'], 'idx_hr_punch_pending');
        });

        Schema::create('hr_regularizations', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->date('work_date');
            $table->dateTime('requested_in')->nullable();
            $table->dateTime('requested_out')->nullable();
            $table->string('reason', 300);
            $table->string('status', 10)->default('requested'); // requested | approved | rejected
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->string('decision_note', 300)->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'work_date']);
        });

        Schema::create('hr_attendance_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->date('work_date');
            $table->foreignId('shift_id')->nullable()->constrained('hr_shifts')->nullOnDelete();
            $table->dateTime('first_in')->nullable();
            $table->dateTime('last_out')->nullable();
            $table->unsignedSmallInteger('worked_minutes')->default(0);
            $table->unsignedSmallInteger('late_minutes')->default(0);
            $table->unsignedSmallInteger('early_out_minutes')->default(0);
            $table->unsignedSmallInteger('overtime_minutes')->default(0);
            $table->string('status', 12); // present | late | half_day | absent | leave | holiday | weekly_off | unrostered
            $table->unsignedBigInteger('leave_request_id')->nullable();
            $table->unsignedBigInteger('regularization_id')->nullable();
            $table->boolean('locked')->default(false);
            $table->timestamps();

            $table->unique(['employee_id', 'work_date'], 'uq_hr_attendance_day');
            $table->index(['organization_id', 'work_date', 'status'], 'idx_hr_attendance_status');
        });

        Schema::create('hr_leave_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('code', 20);
            $table->string('name', 80);
            $table->boolean('is_paid')->default(true);
            $table->decimal('annual_days', 5, 2)->default(0);
            $table->string('accrual', 8)->default('upfront'); // upfront | monthly | none
            $table->decimal('carry_forward_max', 5, 2)->default(0);
            $table->unsignedSmallInteger('max_consecutive_days')->nullable();
            $table->unsignedSmallInteger('document_after_days')->nullable();
            $table->string('gender_restriction', 10)->nullable();
            $table->boolean('encashable')->default(false);
            $table->boolean('requires_hr_approval')->default(false);
            $table->string('status', 10)->default('active');
            $table->timestamps();

            $table->unique(['organization_id', 'code']);
        });

        // The balance is the sum of this ledger. Rows are only ever added; a mistake is reversed by a new row.
        Schema::create('hr_leave_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained('hr_leave_types')->restrictOnDelete();
            $table->string('entry_type', 14); // opening | accrual | taken | reversal | adjustment | carry_forward | encashment | lapse
            $table->decimal('days', 6, 2); // signed
            $table->date('effective_on');
            $table->string('ref_type', 40)->nullable();
            $table->unsignedBigInteger('ref_id')->nullable();
            $table->string('note', 200)->nullable();
            $table->string('idempotency_key', 120);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['organization_id', 'idempotency_key'], 'uq_hr_leave_ledger_key');
            $table->index(['employee_id', 'leave_type_id'], 'idx_hr_leave_ledger_balance');
        });

        Schema::create('hr_leave_requests', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained('hr_leave_types')->restrictOnDelete();
            $table->date('from_date');
            $table->date('to_date');
            $table->decimal('days', 5, 2);
            $table->boolean('half_day')->default(false);
            $table->string('reason', 300)->nullable();
            $table->unsignedBigInteger('document_file_id')->nullable();
            $table->string('status', 16)->default('submitted'); // submitted | manager_approved | approved | rejected | cancelled
            $table->foreignId('manager_decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('manager_decided_at')->nullable();
            $table->foreignId('hr_decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('hr_decided_at')->nullable();
            $table->string('decision_note', 300)->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'from_date']);
            $table->index(['organization_id', 'status']);
        });

        Schema::create('hr_overtime_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->date('work_date');
            $table->unsignedSmallInteger('minutes_claimed');
            $table->unsignedSmallInteger('minutes_approved')->default(0);
            $table->string('day_type', 10)->default('weekday'); // weekday | weekly_off | holiday
            $table->string('status', 10)->default('pending'); // pending | approved | rejected | paid
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->unsignedBigInteger('payroll_run_id')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'work_date'], 'uq_hr_overtime_day');
            $table->index(['organization_id', 'status']);
        });
    }

    public function down(): void
    {
        foreach (['hr_overtime_entries', 'hr_leave_requests', 'hr_leave_ledger', 'hr_leave_types', 'hr_attendance_days', 'hr_regularizations', 'hr_punches', 'hr_devices', 'hr_staffing_requirements', 'hr_roster_entries', 'hr_shifts'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
