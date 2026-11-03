<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * AI and clinical intelligence (Phase 20), part 1: what is allowed to run. Providers (no secret is stored: only the name of the environment variable that holds it), models that must pass an evaluation,
 * registered use cases with a kill switch, versioned prompts, the knowledge items, evaluation sets and runs, and incidents.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_providers', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('kind', 8); // fake | rules | http
            $table->string('location', 14); // on_premise | bangladesh | international
            $table->string('endpoint', 300)->nullable();
            $table->string('default_model', 100)->nullable();
            $table->string('secret_env', 80)->nullable(); // the NAME of the environment variable holding the key
            $table->string('max_level', 2)->default('C3'); // the highest data class it may receive
            $table->boolean('allows_identifiable')->default(false);
            $table->string('legal_ref', 120)->nullable(); // the recorded legal approval for sending identifiable data abroad
            $table->foreignId('legal_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('legal_approved_at')->nullable();
            $table->string('purpose', 300)->nullable();
            $table->string('status', 10)->default('disabled'); // disabled | enabled
            $table->timestamps();
            $table->unique(['organization_id', 'name']);
        });

        Schema::create('ai_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('provider_id')->constrained('ai_providers')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('version', 40);
            $table->string('status', 10)->default('candidate'); // candidate | approved | retired
            $table->unsignedBigInteger('eval_run_id')->nullable(); // the passing run that approved it
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->string('notes', 300)->nullable();
            $table->timestamps();
            $table->unique(['provider_id', 'name', 'version']);
        });

        Schema::create('ai_use_cases', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('code', 40);
            $table->string('name', 150);
            $table->string('intended_use', 500);
            $table->string('risk_class', 6); // low | medium | high
            $table->string('data_level', 2)->default('C4');
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('provider_id')->nullable()->constrained('ai_providers')->nullOnDelete();
            $table->foreignId('model_id')->nullable()->constrained('ai_models')->nullOnDelete();
            $table->string('status', 8)->default('planned'); // planned | enabled | paused | retired
            $table->boolean('implemented')->default(false); // built in this product; a planned one cannot be enabled
            $table->foreignId('registered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->string('paused_reason', 300)->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'code']);
        });

        Schema::create('ai_prompts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('use_case_code', 40);
            $table->unsignedSmallInteger('version');
            $table->text('template');
            $table->string('status', 8)->default('draft'); // draft | active | retired
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['organization_id', 'use_case_code', 'version'], 'ai_prompt_version_unique');
        });

        Schema::create('ai_knowledge', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('title', 200);
            $table->text('body');
            $table->string('status', 8)->default('draft'); // draft | approved | retired
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->index(['organization_id', 'status']);
        });

        Schema::create('ai_eval_sets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('use_case_code', 40);
            $table->string('name', 150);
            $table->timestamps();
            $table->unique(['organization_id', 'name']);
        });

        Schema::create('ai_eval_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('set_id')->constrained('ai_eval_sets')->cascadeOnDelete();
            $table->string('language', 5); // en | bn | mixed
            $table->string('task', 30);
            $table->json('items'); // [{ref, text, kind, untrusted}]
            $table->json('expected'); // facts that must appear
            $table->json('forbidden')->nullable(); // text that must not appear
            $table->string('note', 200)->nullable();
            $table->timestamps();
        });

        Schema::create('ai_eval_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('model_id')->constrained('ai_models')->cascadeOnDelete();
            $table->foreignId('set_id')->constrained('ai_eval_sets')->cascadeOnDelete();
            $table->unsignedSmallInteger('cases');
            $table->unsignedInteger('expected_total');
            $table->unsignedInteger('matched');
            $table->decimal('recall', 5, 4);
            $table->unsignedInteger('unsupported')->default(0);
            $table->unsignedInteger('forbidden_hits')->default(0);
            $table->boolean('passed');
            $table->json('languages');
            $table->json('thresholds');
            $table->foreignId('run_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable();
            $table->index(['model_id', 'created_at']);
        });

        Schema::create('ai_incidents', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('use_case_code', 40)->nullable();
            $table->unsignedBigInteger('output_id')->nullable();
            $table->string('severity', 8); // low | medium | high
            $table->string('kind', 16); // wrong_patient | unsafe_output | data_leak | injection | drift | other
            $table->text('description');
            $table->string('status', 14)->default('open'); // open | investigating | closed
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('resolution', 500)->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->index(['organization_id', 'status']);
        });
    }

    public function down(): void
    {
        foreach (['ai_incidents', 'ai_eval_runs', 'ai_eval_cases', 'ai_eval_sets', 'ai_knowledge', 'ai_prompts', 'ai_use_cases', 'ai_models', 'ai_providers'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
