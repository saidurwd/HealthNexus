<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** A dashboard as it looked for a period, kept so the month's figures can be compared and printed later even after the data moves on. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_dashboard_snapshots', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('dashboard_code', 40);
            $table->string('dashboard_name', 120);
            $table->date('period_from');
            $table->date('period_to');
            $table->string('kind', 10)->default('manual'); // manual | monthly
            $table->foreignId('taken_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('widgets');
            $table->char('checksum', 64);
            $table->timestamps();

            $table->index(['organization_id', 'kind', 'period_from'], 'an_snap_org_kind_from_idx');
            $table->index(['organization_id', 'dashboard_code', 'period_from'], 'an_snap_org_code_from_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_dashboard_snapshots');
    }
};
