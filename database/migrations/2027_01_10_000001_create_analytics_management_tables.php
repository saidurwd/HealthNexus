<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Phase 19, part 1: what the analytics module manages in the operational database — sources and load runs, KPIs, dashboards, reports and schedules, data-quality rules and issues, research datasets, forecasts, opt-ins. (The warehouse tables are in part 2 and may live on another connection.) */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_sources', function (Blueprint $table) {
            $table->id();
            $table->string('key', 40)->unique();
            $table->string('label', 120);
            $table->string('kind', 10); // dimension | fact
            $table->string('mode', 12)->default('incremental'); // incremental | snapshot
            $table->string('watermark', 60)->nullable(); // the last value read (a timestamp or an id)
            $table->boolean('active')->default(true);
            $table->timestamp('last_run_at')->nullable();
            $table->string('last_status', 8)->nullable(); // ok | failed
            $table->unsignedBigInteger('rows_total')->default(0);
            $table->timestamps();
        });

        Schema::create('analytics_loads', function (Blueprint $table) {
            $table->id();
            $table->string('source_key', 40);
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->string('status', 8)->default('running'); // running | ok | failed
            $table->unsignedInteger('rows_read')->default(0);
            $table->unsignedInteger('rows_written')->default(0);
            $table->string('watermark_from', 60)->nullable();
            $table->string('watermark_to', 60)->nullable();
            $table->string('error', 500)->nullable();
            $table->foreignId('triggered_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index(['source_key', 'started_at']);
            $table->index(['status', 'started_at']);
        });

        Schema::create('analytics_kpis', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->nullable()->constrained()->cascadeOnDelete(); // null = the standard catalogue
            $table->string('code', 50);
            $table->string('name', 120);
            $table->string('description', 400)->nullable();
            $table->string('domain', 20); // operations | clinical | diagnostics | pharmacy | finance | people | quality
            $table->string('unit', 10); // count | percent | minutes | days | money | ratio
            $table->string('good_direction', 5)->default('up'); // up | down | none
            $table->json('definition'); // a semantic query: fact, measure, filters
            $table->decimal('target', 14, 2)->nullable();
            $table->unsignedSmallInteger('version')->default(1);
            $table->string('status', 8)->default('active'); // active | retired
            $table->string('owner', 80)->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['organization_id', 'code']);
        });

        Schema::create('analytics_kpi_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kpi_id')->constrained('analytics_kpis')->cascadeOnDelete();
            $table->unsignedSmallInteger('version');
            $table->json('definition');
            $table->string('name', 120);
            $table->string('unit', 10);
            $table->decimal('target', 14, 2)->nullable();
            $table->string('note', 300)->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('changed_at')->nullable();

            $table->unique(['kpi_id', 'version']);
        });

        Schema::create('analytics_kpi_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('kpi_id')->constrained('analytics_kpis')->cascadeOnDelete();
            $table->decimal('target', 14, 2);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['organization_id', 'kpi_id']);
        });

        Schema::create('analytics_dashboards', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('code', 40);
            $table->string('name', 120);
            $table->string('role_key', 20)->default('custom'); // ceo | medical_director | finance | nursing | pharmacy | custom
            $table->boolean('is_system')->default(false);
            $table->boolean('shared')->default(false);
            $table->json('widgets'); // [{type: kpi|chart|table, title, kpi|query, ...}]
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['organization_id', 'code']);
        });

        Schema::create('analytics_reports', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('description', 400)->nullable();
            $table->json('query'); // a semantic query
            $table->boolean('shared')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['organization_id', 'shared']);
        });

        Schema::create('analytics_schedules', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('report_id')->constrained('analytics_reports')->cascadeOnDelete();
            $table->string('frequency', 8); // daily | weekly | monthly
            $table->unsignedTinyInteger('day')->nullable(); // weekday 1-7 or day of month
            $table->string('at_time', 5)->default('07:00');
            $table->string('format', 4)->default('csv'); // csv | pdf
            $table->json('recipients');
            $table->boolean('active')->default(true);
            $table->timestamp('next_run_at')->nullable();
            $table->timestamp('last_run_at')->nullable();
            $table->string('last_status', 8)->nullable(); // sent | failed
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['active', 'next_run_at']);
        });

        Schema::create('analytics_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained('analytics_schedules')->cascadeOnDelete();
            $table->timestamp('run_at');
            $table->string('status', 8); // sent | failed
            $table->unsignedSmallInteger('recipients');
            $table->unsignedInteger('rows')->default(0);
            $table->string('checksum', 64)->nullable();
            $table->string('error', 400)->nullable();
        });

        Schema::create('analytics_dq_rules', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 150);
            $table->string('description', 400)->nullable();
            $table->string('severity', 8)->default('warning'); // warning | error
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('analytics_dq_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('rule_code', 50);
            $table->string('severity', 8);
            $table->string('detail', 300);
            $table->json('sample')->nullable();
            $table->unsignedInteger('failing')->default(0);
            $table->string('status', 8)->default('open'); // open | resolved | ignored
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->string('note', 300)->nullable();
            $table->timestamps();

            $table->index(['status', 'rule_code']);
            $table->index(['organization_id', 'status']);
        });

        Schema::create('analytics_datasets', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('purpose', 400);
            $table->json('definition'); // fact, columns, filters, period
            $table->string('status', 10)->default('requested'); // requested | approved | rejected | generated
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->string('decision_note', 300)->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->unsignedInteger('row_count')->nullable();
            $table->unsignedInteger('suppressed_rows')->nullable();
            $table->string('file', 200)->nullable();
            $table->string('checksum', 64)->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
        });

        Schema::create('analytics_forecasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('metric', 50);
            $table->string('method', 30);
            $table->unsignedSmallInteger('horizon_days');
            $table->decimal('error_pct', 8, 2)->nullable(); // mean absolute percentage error over the back-test
            $table->json('points'); // history and forecast with an interval
            $table->timestamp('generated_at');

            $table->index(['organization_id', 'metric', 'generated_at']);
        });

        Schema::create('analytics_optins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('benchmark')->default(false);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['analytics_optins', 'analytics_forecasts', 'analytics_datasets', 'analytics_dq_issues', 'analytics_dq_rules', 'analytics_deliveries', 'analytics_schedules', 'analytics_reports', 'analytics_dashboards', 'analytics_kpi_targets', 'analytics_kpi_versions', 'analytics_kpis', 'analytics_loads', 'analytics_sources'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
