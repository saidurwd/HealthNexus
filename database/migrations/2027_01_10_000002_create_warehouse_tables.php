<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 19, part 2: the warehouse — dimensions and facts. They are created on the warehouse connection (config analytics.connection; empty = the application's own).
 * No foreign keys: the data may live in another database, and a fact must survive the deletion or merging of its source row. No name, phone, national id or free text of a patient is stored.
 */
return new class extends Migration
{
    private function schema(): Builder
    {
        return Schema::connection(config('analytics.connection'));
    }

    public function up(): void
    {
        $s = $this->schema();
        $money = fn (Blueprint $t, string ...$cols) => array_map(fn ($c) => $t->decimal($c, 18, 2)->default(0), $cols);

        $s->create('dim_date', function (Blueprint $t) {
            $t->unsignedInteger('date_key')->primary(); // 20260910
            $t->date('date')->unique();
            $t->unsignedSmallInteger('year');
            $t->unsignedTinyInteger('quarter');
            $t->unsignedTinyInteger('month');
            $t->string('month_name', 12);
            $t->string('year_month', 7);
            $t->unsignedTinyInteger('week');
            $t->unsignedTinyInteger('day_of_week'); // 1 Monday .. 7 Sunday
            $t->string('day_name', 10);
            $t->boolean('is_weekend');
        });

        $s->create('dim_branch', function (Blueprint $t) {
            $t->unsignedBigInteger('branch_id')->primary();
            $t->unsignedBigInteger('organization_id')->index();
            $t->string('name', 150);
            $t->string('code', 40)->nullable();
            $t->boolean('is_active')->default(true);
        });

        $s->create('dim_department', function (Blueprint $t) {
            $t->unsignedBigInteger('department_id')->primary();
            $t->unsignedBigInteger('organization_id')->index();
            $t->unsignedBigInteger('branch_id')->nullable();
            $t->string('name', 150);
            $t->string('code', 40)->nullable();
        });

        $s->create('dim_provider', function (Blueprint $t) {
            $t->unsignedBigInteger('provider_id')->primary();
            $t->unsignedBigInteger('organization_id')->index();
            $t->unsignedBigInteger('branch_id')->nullable();
            $t->string('name', 150);
            $t->string('provider_type', 40)->nullable();
            $t->unsignedBigInteger('department_id')->nullable();
            $t->string('status', 20)->nullable();
        });

        // A slowly changing dimension (type 2): a new row when age band, sex, district or status changes. The pseudonym is an HMAC; the patient id is not here.
        $s->create('dim_patient', function (Blueprint $t) {
            $t->id('patient_key');
            $t->char('pseudonym', 64);
            $t->unsignedBigInteger('organization_id');
            $t->string('age_band', 8);
            $t->string('sex', 8);
            $t->string('district', 80)->nullable();
            $t->string('patient_status', 12)->nullable();
            $t->boolean('deceased')->default(false);
            $t->date('valid_from');
            $t->date('valid_to')->nullable();
            $t->boolean('is_current')->default(true);
            $t->char('attr_hash', 32);

            $t->index(['pseudonym', 'is_current'], 'dim_patient_pseudonym_current');
            $t->index(['organization_id', 'is_current']);
        });

        $fact = function (string $name, callable $columns, ?array $primary = null) use ($s) {
            $s->create($name, function (Blueprint $t) use ($columns, $primary) {
                $columns($t);
                $t->timestamp('loaded_at')->nullable();
                $primary && $t->primary($primary);
            });
        };

        $fact('fact_encounters', function (Blueprint $t) {
            $t->unsignedBigInteger('encounter_id')->primary();
            $t->unsignedBigInteger('organization_id');
            $t->unsignedBigInteger('branch_id')->nullable();
            $t->unsignedInteger('date_key');
            $t->unsignedBigInteger('department_id')->nullable();
            $t->unsignedBigInteger('provider_id')->nullable();
            $t->unsignedBigInteger('patient_key')->nullable();
            $t->string('encounter_type', 30)->nullable();
            $t->string('status', 20)->nullable();
            $t->string('priority', 12)->nullable();
            $t->string('source', 20)->nullable();
            $t->unsignedInteger('duration_minutes')->nullable();
            $t->unsignedTinyInteger('hour')->nullable();
            $t->boolean('completed')->default(false);
            $t->index(['organization_id', 'date_key']);
        });

        $fact('fact_appointments', function (Blueprint $t) {
            $t->unsignedBigInteger('appointment_id')->primary();
            $t->unsignedBigInteger('organization_id');
            $t->unsignedBigInteger('branch_id')->nullable();
            $t->unsignedInteger('date_key');
            $t->unsignedBigInteger('provider_id')->nullable();
            $t->unsignedBigInteger('patient_key')->nullable();
            $t->string('type', 30)->nullable();
            $t->string('source', 20)->nullable();
            $t->string('status', 20)->nullable();
            $t->integer('lead_days')->nullable();
            $t->integer('wait_minutes')->nullable();
            $t->boolean('is_no_show')->default(false);
            $t->boolean('is_cancelled')->default(false);
            $t->boolean('is_walk_in')->default(false);
            $t->boolean('is_completed')->default(false);
            $t->index(['organization_id', 'date_key']);
        });

        $fact('fact_invoices', function (Blueprint $t) use ($money) {
            $t->unsignedBigInteger('invoice_id')->primary();
            $t->unsignedBigInteger('organization_id');
            $t->unsignedBigInteger('branch_id')->nullable();
            $t->unsignedInteger('date_key');
            $t->unsignedBigInteger('patient_key')->nullable();
            $t->string('invoice_type', 20)->nullable();
            $t->string('status', 20)->nullable();
            $t->string('payer_kind', 10); // self | corporate | insurance
            $t->boolean('is_billed')->default(false); // finalised and not cancelled
            $money($t, 'subtotal', 'discount', 'tax', 'rounding', 'grand_total', 'paid', 'due');
            $t->index(['organization_id', 'date_key']);
        });

        $fact('fact_revenue_lines', function (Blueprint $t) use ($money) {
            $t->unsignedBigInteger('invoice_item_id')->primary();
            $t->unsignedBigInteger('invoice_id')->index();
            $t->unsignedBigInteger('organization_id');
            $t->unsignedBigInteger('branch_id')->nullable();
            $t->unsignedInteger('date_key');
            $t->unsignedBigInteger('department_id')->nullable();
            $t->unsignedBigInteger('provider_id')->nullable();
            $t->string('category_code', 40)->nullable();
            $t->boolean('is_billed')->default(false);
            $money($t, 'gross', 'discount', 'tax', 'net');
            $t->index(['organization_id', 'date_key']);
        });

        $fact('fact_payments', function (Blueprint $t) use ($money) {
            $t->unsignedBigInteger('payment_id')->primary();
            $t->unsignedBigInteger('organization_id');
            $t->unsignedBigInteger('branch_id')->nullable();
            $t->unsignedInteger('date_key');
            $t->string('method_type', 30)->nullable();
            $t->string('status', 20)->nullable();
            $money($t, 'amount');
            $t->index(['organization_id', 'date_key']);
        });

        $fact('fact_lab_orders', function (Blueprint $t) {
            $t->unsignedBigInteger('lab_order_id')->primary();
            $t->unsignedBigInteger('organization_id');
            $t->unsignedBigInteger('branch_id')->nullable();
            $t->unsignedInteger('date_key');
            $t->unsignedBigInteger('department_id')->nullable();
            $t->unsignedBigInteger('patient_key')->nullable();
            $t->string('priority', 12)->nullable();
            $t->string('status', 20)->nullable();
            $t->unsignedSmallInteger('items')->default(0);
            $t->integer('tat_collect_min')->nullable();
            $t->integer('tat_report_min')->nullable();
            $t->unsignedSmallInteger('critical_results')->default(0);
            $t->unsignedSmallInteger('abnormal_results')->default(0);
            $t->boolean('reported')->default(false);
            $t->index(['organization_id', 'date_key']);
        });

        $fact('fact_radiology_exams', function (Blueprint $t) {
            $t->unsignedBigInteger('examination_id')->primary();
            $t->unsignedBigInteger('organization_id');
            $t->unsignedBigInteger('branch_id')->nullable();
            $t->unsignedInteger('date_key');
            $t->unsignedBigInteger('modality_id')->nullable();
            $t->unsignedBigInteger('patient_key')->nullable();
            $t->string('priority', 12)->nullable();
            $t->string('status', 20)->nullable();
            $t->integer('tat_report_min')->nullable();
            $t->boolean('reported')->default(false);
            $t->index(['organization_id', 'date_key']);
        });

        $fact('fact_dispensings', function (Blueprint $t) {
            $t->unsignedBigInteger('dispensing_id')->primary();
            $t->unsignedBigInteger('organization_id');
            $t->unsignedBigInteger('branch_id')->nullable();
            $t->unsignedInteger('date_key');
            $t->unsignedBigInteger('store_id')->nullable();
            $t->unsignedBigInteger('patient_key')->nullable();
            $t->string('status', 20)->nullable();
            $t->unsignedSmallInteger('items')->default(0);
            $t->decimal('quantity', 14, 2)->default(0);
            $t->integer('tat_minutes')->nullable();
            $t->index(['organization_id', 'date_key']);
        });

        $fact('fact_admissions', function (Blueprint $t) {
            $t->unsignedBigInteger('admission_id')->primary();
            $t->unsignedBigInteger('organization_id');
            $t->unsignedBigInteger('branch_id')->nullable();
            $t->unsignedInteger('date_key');
            $t->unsignedInteger('discharge_date_key')->nullable();
            $t->unsignedBigInteger('department_id')->nullable();
            $t->unsignedBigInteger('provider_id')->nullable();
            $t->unsignedBigInteger('patient_key')->nullable();
            $t->string('status', 20)->nullable();
            $t->unsignedBigInteger('disposition_id')->nullable();
            $t->decimal('los_days', 8, 2)->nullable();
            $t->boolean('is_discharged')->default(false);
            $t->index(['organization_id', 'date_key']);
        });

        $fact('fact_bed_census', function (Blueprint $t) {
            $t->unsignedInteger('date_key');
            $t->unsignedBigInteger('organization_id');
            $t->unsignedBigInteger('branch_id');
            $t->unsignedInteger('beds_total')->default(0);
            $t->unsignedInteger('beds_occupied')->default(0);
        }, ['date_key', 'organization_id', 'branch_id']);

        $fact('fact_surgeries', function (Blueprint $t) {
            $t->unsignedBigInteger('surgery_id')->primary();
            $t->unsignedBigInteger('organization_id');
            $t->unsignedBigInteger('branch_id')->nullable();
            $t->unsignedInteger('date_key');
            $t->unsignedBigInteger('theatre_id')->nullable();
            $t->unsignedBigInteger('specialty_id')->nullable();
            $t->string('priority', 12)->nullable();
            $t->string('surgery_type', 20)->nullable();
            $t->string('status', 20)->nullable();
            $t->integer('planned_minutes')->nullable();
            $t->integer('actual_minutes')->nullable();
            $t->integer('start_delay_minutes')->nullable();
            $t->boolean('completed')->default(false);
            $t->boolean('cancelled')->default(false);
            $t->index(['organization_id', 'date_key']);
        });

        $fact('fact_icu_stays', function (Blueprint $t) {
            $t->unsignedBigInteger('episode_id')->primary();
            $t->unsignedBigInteger('organization_id');
            $t->unsignedBigInteger('branch_id')->nullable();
            $t->unsignedInteger('date_key');
            $t->unsignedBigInteger('patient_key')->nullable();
            $t->string('status', 20)->nullable();
            $t->decimal('los_hours', 10, 2)->nullable();
            $t->boolean('discharged')->default(false);
            $t->index(['organization_id', 'date_key']);
        });

        $fact('fact_ed_visits', function (Blueprint $t) {
            $t->unsignedBigInteger('visit_id')->primary();
            $t->unsignedBigInteger('organization_id');
            $t->unsignedBigInteger('branch_id')->nullable();
            $t->unsignedInteger('date_key');
            $t->unsignedTinyInteger('hour')->nullable();
            $t->string('triage_level', 12)->nullable();
            $t->string('status', 20)->nullable();
            $t->integer('door_to_triage_min')->nullable();
            $t->integer('door_to_doctor_min')->nullable();
            $t->integer('los_minutes')->nullable();
            $t->string('disposition_type', 30)->nullable();
            $t->boolean('seen')->default(false);
            $t->index(['organization_id', 'date_key']);
        });

        $fact('fact_claims', function (Blueprint $t) use ($money) {
            $t->unsignedBigInteger('claim_id')->primary();
            $t->unsignedBigInteger('organization_id');
            $t->unsignedBigInteger('branch_id')->nullable();
            $t->unsignedInteger('date_key');
            $t->unsignedBigInteger('payer_id')->nullable();
            $t->string('status', 20)->nullable();
            $money($t, 'gross', 'payer_share', 'paid', 'adjusted', 'outstanding');
            $t->integer('age_days')->default(0);
            $t->boolean('is_denied')->default(false);
            $t->boolean('is_open')->default(false);
            $t->index(['organization_id', 'date_key']);
        });

        $fact('fact_stock_daily', function (Blueprint $t) {
            $t->unsignedInteger('date_key');
            $t->unsignedBigInteger('organization_id');
            $t->unsignedBigInteger('store_id');
            $t->unsignedInteger('items_tracked')->default(0);
            $t->unsignedInteger('items_out')->default(0);
        }, ['date_key', 'organization_id', 'store_id']);

        $fact('fact_daily_metrics', function (Blueprint $t) {
            $t->unsignedInteger('date_key');
            $t->unsignedBigInteger('organization_id');
            $t->unsignedBigInteger('branch_id')->default(0);
            $t->string('metric', 40);
            $t->decimal('value', 18, 2)->default(0);
        }, ['date_key', 'organization_id', 'branch_id', 'metric']);

        $fact('fact_ledger_monthly', function (Blueprint $t) use ($money) {
            $t->unsignedBigInteger('organization_id');
            $t->string('period_ym', 7);
            $t->string('account_code', 20);
            $t->string('account_name', 150);
            $t->string('account_type', 10);
            $money($t, 'debit', 'credit', 'closing');
        }, ['organization_id', 'period_ym', 'account_code']);
    }

    public function down(): void
    {
        foreach (['fact_ledger_monthly', 'fact_daily_metrics', 'fact_stock_daily', 'fact_claims', 'fact_ed_visits', 'fact_icu_stays', 'fact_surgeries', 'fact_bed_census', 'fact_admissions', 'fact_dispensings', 'fact_radiology_exams', 'fact_lab_orders', 'fact_payments', 'fact_revenue_lines',
            'fact_invoices', 'fact_appointments', 'fact_encounters', 'dim_patient', 'dim_provider', 'dim_department', 'dim_branch', 'dim_date'] as $t) {
            $this->schema()->dropIfExists($t);
        }
    }
};
