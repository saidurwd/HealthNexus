<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ed_protocol_runs', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('visit_id')->constrained('ed_visits')->cascadeOnDelete();
            $table->string('protocol', 30);
            $table->string('status', 20)->default('active'); // active | completed | cancelled
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->foreignId('started_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['visit_id', 'protocol', 'status'], 'ed_prun_visit_proto_idx');
        });

        Schema::create('ed_protocol_steps', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('run_id')->constrained('ed_protocol_runs')->cascadeOnDelete();
            $table->string('step_key', 40);
            $table->string('label', 200);
            $table->timestamp('due_at')->nullable();
            $table->timestamp('done_at')->nullable();
            $table->foreignId('done_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note', 500)->nullable();
            $table->timestamps();

            $table->unique(['run_id', 'step_key']);
            $table->index(['done_at', 'due_at']);
        });

        Schema::create('ed_injuries', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('visit_id')->constrained('ed_visits')->cascadeOnDelete();
            $table->string('body_region', 30);
            $table->string('injury_type', 30);
            $table->string('severity', 20);
            $table->string('notes', 500)->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('visit_id');
        });

        // Mass-casualty mode is a switch on the branch, not a second patient table: arrivals during an activation are flagged on the visit.
        Schema::create('ed_mci_activations', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('reason', 300)->nullable();
            $table->timestamp('activated_at');
            $table->foreignId('activated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('deactivated_at')->nullable();
            $table->foreignId('deactivated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['branch_id', 'deactivated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ed_mci_activations');
        Schema::dropIfExists('ed_injuries');
        Schema::dropIfExists('ed_protocol_steps');
        Schema::dropIfExists('ed_protocol_runs');
    }
};
