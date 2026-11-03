<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Disease watch (Phase 26, B5): the notifiable conditions a hospital watches, and the signals raised when the cases of one rise above what is usual. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sv_conditions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('code', 30);
            $table->string('name', 100);
            $table->string('name_bn', 100)->nullable();
            $table->json('icd_prefixes'); // diagnosis codes that count, by their first letters/digits, e.g. ["A90","A91"]
            $table->unsignedSmallInteger('window_days')->default(7);
            $table->unsignedSmallInteger('min_cases')->default(3); // fewest distinct patients in the window that can raise a signal
            $table->decimal('spike_factor', 4, 1)->default(2.0); // times the usual (the average of the earlier windows)
            $table->unsignedSmallInteger('cluster_cases')->default(3); // distinct patients from one district in the window that make a local cluster
            $table->string('severity', 10)->default('warning'); // warning | critical
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['organization_id', 'code']);
        });

        Schema::create('sv_signals', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('condition_id')->constrained('sv_conditions')->cascadeOnDelete();
            $table->foreignId('district_id')->nullable()->constrained('geo_districts')->nullOnDelete(); // null: the hospital as a whole
            $table->date('week_start'); // Monday of the week the window ends in
            $table->date('window_from');
            $table->date('window_to');
            $table->unsignedSmallInteger('cases'); // distinct patients in the window
            $table->decimal('baseline', 6, 1)->nullable(); // the usual per window
            $table->string('reason', 10); // spike | cluster | first
            $table->string('status', 14)->default('new'); // new | investigating | confirmed | dismissed
            $table->string('decision_note', 500)->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->timestamp('reported_at')->nullable(); // told to the health authority
            $table->string('reported_to', 120)->nullable();
            $table->string('report_reference', 60)->nullable();
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('raised_at');
            $table->timestamps();

            $table->unique(['organization_id', 'condition_id', 'district_id', 'week_start'], 'sv_signal_unique');
            $table->index(['organization_id', 'status'], 'sv_signal_org_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sv_signals');
        Schema::dropIfExists('sv_conditions');
    }
};
