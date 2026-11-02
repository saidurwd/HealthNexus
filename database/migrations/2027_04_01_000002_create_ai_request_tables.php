<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * AI and clinical intelligence (Phase 20), part 2: what happened. Every call is an immutable request row (allowed, blocked or failed) holding the redacted inputs it was given; its output is a draft
 * suggestion whose generated text can never change (only its review status does); feedback is separate. Triggers enforce both by raw SQL as well.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_requests', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('use_case_code', 40);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('patient_id')->nullable();
            $table->unsignedBigInteger('provider_id')->nullable();
            $table->string('model_name', 100)->nullable();
            $table->string('model_version', 40)->nullable();
            $table->unsignedSmallInteger('prompt_version')->nullable();
            $table->string('task', 30);
            $table->string('mode', 14)->nullable(); // identifiable | deidentified
            $table->string('outcome', 10); // completed | blocked | failed
            $table->string('blocked_reason', 300)->nullable();
            $table->unsignedSmallInteger('injection_score')->default(0);
            $table->unsignedSmallInteger('suppressed')->default(0);
            $table->json('input')->nullable(); // the redacted items the provider was given
            $table->unsignedInteger('latency_ms')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->index(['organization_id', 'use_case_code', 'created_at'], 'ai_req_usecase_idx');
            $table->index(['organization_id', 'outcome']);
        });

        Schema::create('ai_outputs', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('request_id')->constrained('ai_requests')->cascadeOnDelete();
            $table->string('use_case_code', 40);
            $table->unsignedBigInteger('patient_id')->nullable();
            $table->string('kind', 14); // summary | note_draft | answer | forecast | query | none
            $table->string('title', 200);
            $table->longText('text');
            $table->json('lines'); // [{text, refs, section}]
            $table->json('sources'); // [{ref, label}]
            $table->string('label', 60);
            $table->string('uncertainty', 200)->nullable();
            $table->unsignedSmallInteger('suppressed')->default(0);
            $table->string('status', 10)->default('draft'); // draft | accepted | rejected | expired
            $table->timestamp('expires_at');
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('review_note', 500)->nullable();
            $table->longText('edited_text')->nullable(); // the reviewer's version; the generated text above is never changed
            $table->timestamps();
            $table->index(['organization_id', 'status', 'created_at'], 'ai_out_status_idx');
            $table->index(['organization_id', 'patient_id']);
        });

        Schema::create('ai_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('output_id')->constrained('ai_outputs')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('rating', 10); // useful | not_useful
            $table->string('error_type', 14)->nullable(); // wrong_fact | missing | wrong_patient | unsafe | other
            $table->string('comment', 500)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->index(['organization_id', 'created_at']);
        });

        $this->protect();
    }

    private function protect(): void
    {
        $sqlite = DB::getDriverName() === 'sqlite';
        // requests: never changed, never deleted
        foreach (['update' => 'UPDATE', 'delete' => 'DELETE'] as $k => $op) {
            DB::unprepared($sqlite
                ? "CREATE TRIGGER ai_requests_no_$k BEFORE $op ON ai_requests BEGIN SELECT RAISE(ABORT, 'an AI request record only grows'); END"
                : "CREATE TRIGGER ai_requests_no_$k BEFORE $op ON ai_requests FOR EACH ROW BEGIN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'an AI request record only grows'; END");
        }
        // outputs: what was generated never changes; the review fields do
        $frozen = ['organization_id', 'request_id', 'use_case_code', 'patient_id', 'kind', 'title', 'text', 'lines', 'sources', 'label', 'uncertainty', 'suppressed'];
        $changed = collect($frozen)->map(fn ($c) => $sqlite ? "OLD.$c IS NOT NEW.$c" : "NOT (OLD.$c <=> NEW.$c)")->implode(' OR ');
        $text = 'what the AI generated cannot be changed: edit your own version';
        DB::unprepared($sqlite
            ? "CREATE TRIGGER ai_outputs_frozen BEFORE UPDATE ON ai_outputs WHEN $changed BEGIN SELECT RAISE(ABORT, '$text'); END"
            : "CREATE TRIGGER ai_outputs_frozen BEFORE UPDATE ON ai_outputs FOR EACH ROW BEGIN IF $changed THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = '$text'; END IF; END");
        DB::unprepared($sqlite
            ? "CREATE TRIGGER ai_outputs_no_delete BEFORE DELETE ON ai_outputs BEGIN SELECT RAISE(ABORT, 'an AI output cannot be deleted'); END"
            : "CREATE TRIGGER ai_outputs_no_delete BEFORE DELETE ON ai_outputs FOR EACH ROW BEGIN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'an AI output cannot be deleted'; END");
    }

    public function down(): void
    {
        foreach (['ai_requests_no_update', 'ai_requests_no_delete', 'ai_outputs_frozen', 'ai_outputs_no_delete'] as $t) {
            DB::unprepared("DROP TRIGGER IF EXISTS $t");
        }
        foreach (['ai_feedback', 'ai_outputs', 'ai_requests'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
