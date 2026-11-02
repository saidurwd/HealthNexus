<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Facility and support services (Phase 22), part 1: the equipment register, maintenance and calibration, housekeeping. Index names over 64 characters are named by hand. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fac_assets', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('tag', 30);
            $table->string('name', 150);
            $table->string('category', 12); // biomedical | it | furniture | vehicle | plant | other
            $table->string('manufacturer', 100)->nullable();
            $table->string('model', 100)->nullable();
            $table->string('serial_no', 80)->nullable();
            $table->string('supplier_name', 150)->nullable();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('location', 120)->nullable();
            $table->date('purchased_on')->nullable();
            $table->decimal('cost', 14, 2)->nullable();
            $table->date('warranty_until')->nullable();
            $table->string('risk_class', 6)->default('medium'); // low | medium | high
            $table->string('status', 20)->default('in_service'); // in_service | under_maintenance | out_of_service | retired
            $table->unsignedBigInteger('fin_asset_id')->nullable(); // Finance's fixed-asset record (depreciation lives there)
            $table->char('qr_token', 32)->unique();
            $table->string('note', 500)->nullable();
            $table->date('retired_on')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'tag']);
            $table->index(['organization_id', 'status']);
            $table->index(['organization_id', 'category']);
        });

        Schema::create('fac_maintenance_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained('fac_assets')->cascadeOnDelete();
            $table->string('kind', 14); // preventive | calibration | safety_test
            $table->string('title', 150);
            $table->unsignedSmallInteger('interval_days');
            $table->date('last_done_on')->nullable();
            $table->date('next_due_on');
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->index(['organization_id', 'active', 'next_due_on'], 'fac_plans_due_idx');
        });

        Schema::create('fac_work_orders', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('number', 30);
            $table->foreignId('asset_id')->nullable()->constrained('fac_assets')->nullOnDelete();
            $table->foreignId('plan_id')->nullable()->constrained('fac_maintenance_plans')->nullOnDelete();
            $table->string('kind', 12); // preventive | calibration | breakdown | inspection | other
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->string('priority', 8)->default('normal'); // low | normal | urgent
            $table->string('status', 14)->default('open'); // open | in_progress | waiting_parts | done | verified | cancelled
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reported_at');
            $table->date('due_on')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('completion_note', 1000)->nullable();
            $table->unsignedInteger('downtime_minutes')->nullable();
            $table->decimal('cost', 12, 2)->default(0);
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'number']);
            $table->index(['organization_id', 'status', 'priority'], 'fac_wo_status_idx');
            $table->index(['asset_id', 'status']);
        });

        Schema::create('fac_work_order_parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained('fac_work_orders')->cascadeOnDelete();
            $table->unsignedBigInteger('item_id')->nullable(); // the stores item, when one is used
            $table->string('description', 200);
            $table->decimal('quantity', 10, 2);
            $table->decimal('unit_cost', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('fac_calibrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained('fac_assets')->cascadeOnDelete();
            $table->foreignId('work_order_id')->nullable()->constrained('fac_work_orders')->nullOnDelete();
            $table->date('performed_on');
            $table->date('next_due_on')->nullable();
            $table->string('result', 10); // pass | fail | adjusted
            $table->string('certificate_no', 60)->nullable();
            $table->string('performed_by', 120)->nullable();
            $table->string('note', 500)->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['asset_id', 'performed_on']);
        });

        Schema::create('fac_cleaning_tasks', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('kind', 20); // bed_turnover | isolation_terminal | room | theatre
            $table->unsignedBigInteger('bed_id')->nullable(); // ipd_beds.id
            $table->string('location', 120);
            $table->string('source_type', 30)->nullable(); // bed_allocation
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('priority', 8)->default('normal');
            $table->string('status', 12)->default('pending'); // pending | in_progress | done | inspected | cancelled
            $table->timestamp('requested_at');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('done_at')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('done_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('inspected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('inspected_at')->nullable();
            $table->json('checklist')->nullable(); // [{item, done}]
            $table->unsignedInteger('turnaround_minutes')->nullable();
            $table->boolean('bed_released')->default(false);
            $table->string('note', 500)->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'source_type', 'source_id'], 'fac_clean_source_unique');
            $table->index(['organization_id', 'status', 'requested_at'], 'fac_clean_status_idx');
        });

        Schema::create('fac_cleaning_audits', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('area', 120);
            $table->date('audited_on');
            $table->unsignedSmallInteger('items_checked');
            $table->unsignedSmallInteger('items_passed');
            $table->decimal('score', 5, 2);
            $table->json('findings')->nullable();
            $table->foreignId('auditor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['organization_id', 'audited_on']);
        });
    }

    public function down(): void
    {
        foreach (['fac_cleaning_audits', 'fac_cleaning_tasks', 'fac_calibrations', 'fac_work_order_parts', 'fac_work_orders', 'fac_maintenance_plans', 'fac_assets'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
