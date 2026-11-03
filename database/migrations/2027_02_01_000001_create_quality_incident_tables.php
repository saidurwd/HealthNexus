<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Quality, safety and compliance (Phase 23), part 1: incidents and what follows from them.
 *
 * An incident is a frozen report plus an amendment trail: what was reported never changes (the model refuses it and so do the triggers below),
 * management fields move through the workflow, and a correction is an amendment row. The reporter is NOT on the incident: qa_incident_reporters
 * holds a user only when the reporter chose to be named, and an anonymous report has no such row.
 */
return new class extends Migration
{
    /** The reported facts: none of these may change after the row is saved. */
    private const FROZEN = ['organization_id', 'branch_id', 'number', 'kind', 'category', 'harm', 'occurred_at', 'department_id', 'location', 'patient_id', 'description', 'immediate_action', 'is_anonymous', 'source_type', 'source_id'];

    public function up(): void
    {
        Schema::create('qa_incidents', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('number', 30);
            $table->string('kind', 14); // incident | near_miss | adverse_event | sentinel
            $table->string('category', 24);
            $table->string('harm', 12); // none | minor | moderate | major | catastrophic (as reported)
            $table->timestamp('occurred_at');
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('location', 120)->nullable();
            $table->foreignId('patient_id')->nullable()->constrained()->nullOnDelete();
            $table->text('description');
            $table->string('immediate_action', 1000)->nullable();
            $table->boolean('is_anonymous')->default(false);
            $table->string('source_type', 40)->nullable(); // blood_incident | pharmacy_safety_alert | ... when the sweep made it
            $table->unsignedBigInteger('source_id')->nullable();
            // management (these move with the workflow)
            $table->string('status', 14)->default('reported'); // reported | triaged | investigating | actioned | closed
            $table->string('review_harm', 12)->nullable(); // the harm level after review
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('triaged_at')->nullable();
            $table->date('due_on')->nullable();
            $table->string('closure_note', 1000)->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['organization_id', 'number']);
            $table->unique(['organization_id', 'source_type', 'source_id'], 'qa_incidents_source_unique');
            $table->index(['organization_id', 'status', 'occurred_at'], 'qa_incidents_status_idx');
            $table->index(['organization_id', 'category', 'occurred_at'], 'qa_incidents_category_idx');
        });

        Schema::create('qa_incident_reporters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('incident_id')->unique()->constrained('qa_incidents')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('qa_incident_amendments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('incident_id')->constrained('qa_incidents')->cascadeOnDelete();
            $table->string('field', 30);
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->string('reason', 500);
            $table->foreignId('amended_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable();
            $table->index(['incident_id', 'created_at']);
        });

        Schema::create('qa_incident_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('incident_id')->constrained('qa_incidents')->cascadeOnDelete();
            $table->string('event', 24); // reported | triaged | assigned | investigation | action | closed | reopened | revealed
            $table->string('note', 1000)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // never set on "reported" for an anonymous report
            $table->timestamp('created_at')->nullable();
            $table->index(['incident_id', 'created_at']);
        });

        Schema::create('qa_investigations', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('incident_id')->unique()->constrained('qa_incidents')->cascadeOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('method', 16)->default('five_whys'); // five_whys | fishbone | timeline | other
            $table->string('status', 12)->default('open'); // open | submitted | accepted | returned
            $table->text('findings')->nullable();
            $table->json('root_causes')->nullable(); // [{category, text}] — systems, never persons
            $table->timestamp('started_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('review_note', 500)->nullable();
            $table->timestamps();
        });

        Schema::create('qa_actions', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('number', 30);
            $table->string('source_type', 24)->nullable(); // incident | audit | meeting | assessment | mortality | infection | outbreak
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('action_type', 12); // immediate | corrective | preventive | improvement
            $table->string('description', 1000);
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->date('due_on');
            $table->string('status', 12)->default('open'); // open | in_progress | done | verified | cancelled
            $table->timestamp('done_at')->nullable();
            $table->string('done_note', 1000)->nullable(); // what was done / the evidence
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->boolean('effective')->nullable();
            $table->string('verify_note', 500)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('overdue_alerted_at')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'number']);
            $table->index(['organization_id', 'status', 'due_on'], 'qa_actions_status_due_idx');
            $table->index(['source_type', 'source_id']);
            $table->index(['owner_id', 'status']);
        });

        Schema::create('qa_complaint_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('complaint_id'); // crm_complaints.id — linked, never copied
            $table->foreignId('incident_id')->nullable()->constrained('qa_incidents')->cascadeOnDelete();
            $table->foreignId('action_id')->nullable()->constrained('qa_actions')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable();
            $table->unique(['complaint_id', 'incident_id'], 'qa_complaint_incident_unique');
            $table->index(['organization_id', 'complaint_id']);
        });

        $this->protect();
    }

    /** What was reported cannot be changed or deleted, and the trails only grow — by the application or by raw SQL. */
    private function protect(): void
    {
        $sqlite = DB::getDriverName() === 'sqlite';
        $changed = collect(self::FROZEN)->map(fn ($c) => $sqlite ? "OLD.$c IS NOT NEW.$c" : "NOT (OLD.$c <=> NEW.$c)")->implode(' OR ');
        $deny = fn (string $text) => $sqlite ? "BEGIN SELECT RAISE(ABORT, '$text'); END" : "FOR EACH ROW BEGIN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = '$text'; END";

        DB::unprepared('CREATE TRIGGER qa_incidents_frozen BEFORE UPDATE ON qa_incidents '.($sqlite ? "WHEN $changed " : 'FOR EACH ROW BEGIN IF '.$changed." THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'a reported incident cannot be changed: amend it'; END IF; END")
            .($sqlite ? "BEGIN SELECT RAISE(ABORT, 'a reported incident cannot be changed: amend it'); END" : ''));
        DB::unprepared('CREATE TRIGGER qa_incidents_no_delete BEFORE DELETE ON qa_incidents '.$deny('an incident cannot be deleted'));
        foreach (['qa_incident_amendments', 'qa_incident_events', 'qa_incident_reporters'] as $t) {
            DB::unprepared("CREATE TRIGGER {$t}_no_update BEFORE UPDATE ON $t ".$deny('this trail only grows'));
            DB::unprepared("CREATE TRIGGER {$t}_no_delete BEFORE DELETE ON $t ".$deny('this trail only grows'));
        }
    }

    public function down(): void
    {
        foreach (['qa_incidents_frozen', 'qa_incidents_no_delete'] as $t) {
            DB::unprepared("DROP TRIGGER IF EXISTS $t");
        }
        foreach (['qa_incident_amendments', 'qa_incident_events', 'qa_incident_reporters'] as $t) {
            DB::unprepared("DROP TRIGGER IF EXISTS {$t}_no_update");
            DB::unprepared("DROP TRIGGER IF EXISTS {$t}_no_delete");
        }
        foreach (['qa_complaint_links', 'qa_actions', 'qa_investigations', 'qa_incident_events', 'qa_incident_amendments', 'qa_incident_reporters', 'qa_incidents'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
