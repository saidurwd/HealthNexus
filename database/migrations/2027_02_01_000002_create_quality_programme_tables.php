<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Quality, safety and compliance (Phase 23), part 2: infection control, indicators and audits, mortality review, document control,
 * privileging and accreditation, committees. Index names that would pass 64 characters are named by hand.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ---- infection control
        Schema::create('qa_infection_cases', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('admission_id')->nullable(); // ipd_admissions.id
            $table->unsignedBigInteger('ward_id')->nullable(); // ipd_wards.id
            $table->string('hai_type', 12); // clabsi | cauti | ssi | vap | cdiff | mrsa | other
            $table->string('organism', 80)->nullable();
            $table->string('device', 30)->nullable();
            $table->date('onset_on');
            $table->unsignedSmallInteger('hospital_day')->nullable(); // days since admission at onset: 3 or more is hospital-acquired
            $table->string('status', 10)->default('suspected'); // suspected | confirmed | ruled_out
            $table->string('note', 500)->nullable();
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
            $table->index(['organization_id', 'onset_on'], 'qa_infection_onset_idx');
            $table->index(['organization_id', 'ward_id', 'hai_type'], 'qa_infection_ward_idx');
        });

        Schema::create('qa_isolations', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('admission_id')->nullable();
            $table->string('precaution', 12); // contact | droplet | airborne | protective | enteric
            $table->string('reason', 200);
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->foreignId('ordered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('ended_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['patient_id', 'ended_at']);
            $table->index(['organization_id', 'ended_at']);
        });

        Schema::create('qa_hand_hygiene_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('ward_id')->nullable();
            $table->string('ward_name', 80)->nullable();
            $table->date('audited_on');
            $table->string('staff_role', 12); // doctor | nurse | support | other
            $table->unsignedSmallInteger('moments_observed');
            $table->unsignedSmallInteger('moments_compliant');
            $table->foreignId('auditor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note', 300)->nullable();
            $table->timestamps();
            $table->index(['organization_id', 'audited_on'], 'qa_hh_audited_idx');
        });

        Schema::create('qa_outbreaks', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('ward_id')->nullable();
            $table->string('organism', 80)->nullable();
            $table->string('hai_type', 12)->nullable();
            $table->unsignedSmallInteger('case_count');
            $table->date('opened_on');
            $table->string('status', 10)->default('open'); // open | contained | closed
            $table->date('closed_on')->nullable();
            $table->string('note', 500)->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['organization_id', 'status']);
        });

        // ---- indicators, clinical audit, mortality review
        Schema::create('qa_indicators', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->nullable()->constrained()->cascadeOnDelete(); // null = standard
            $table->string('code', 40);
            $table->string('name', 150);
            $table->string('description', 500)->nullable();
            $table->string('domain', 20); // outcome | safety | infection | process | workforce
            $table->string('unit', 10); // percent | per_1000 | count | days
            $table->string('good_direction', 5); // down | up
            $table->string('formula', 40); // the key of the calculation
            $table->decimal('target', 12, 4)->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique(['organization_id', 'code']);
        });

        Schema::create('qa_indicator_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('indicator_id')->constrained('qa_indicators')->cascadeOnDelete();
            $table->date('period'); // first day of the month
            $table->decimal('numerator', 14, 2)->default(0);
            $table->decimal('denominator', 14, 2)->default(0);
            $table->decimal('value', 14, 4)->nullable(); // null when the denominator is 0
            $table->timestamp('computed_at')->nullable();
            $table->unique(['organization_id', 'indicator_id', 'period'], 'qa_indicator_period_unique');
        });

        Schema::create('qa_audits', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('number', 30);
            $table->string('title', 200);
            $table->string('standard', 500)->nullable(); // what good looks like
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('auditor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('audited_on');
            $table->unsignedSmallInteger('sample_size')->default(0);
            $table->string('status', 12)->default('in_progress'); // planned | in_progress | completed
            $table->decimal('compliance', 5, 2)->nullable();
            $table->text('summary')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'number']);
        });

        Schema::create('qa_audit_findings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audit_id')->constrained('qa_audits')->cascadeOnDelete();
            $table->string('criterion', 300);
            $table->unsignedSmallInteger('met');
            $table->unsignedSmallInteger('not_met');
            $table->string('comment', 500)->nullable();
        });

        Schema::create('qa_mortality_reviews', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('number', 30);
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('source_type', 20); // ipd_admission | ed_visit
            $table->unsignedBigInteger('source_id');
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('died_at');
            $table->string('status', 10)->default('pending'); // pending | reviewed | discussed
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('classification', 26)->nullable(); // expected | unexpected_unavoidable | potentially_preventable | preventable
            $table->text('findings')->nullable();
            $table->string('learning', 1000)->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'number']);
            $table->unique(['organization_id', 'source_type', 'source_id'], 'qa_mortality_source_unique');
            $table->index(['organization_id', 'status', 'died_at'], 'qa_mortality_status_idx');
        });

        // ---- document control
        Schema::create('qa_documents', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('code', 30);
            $table->string('title', 200);
            $table->string('category', 12); // policy | sop | protocol | guideline | form
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('review_months')->default(12);
            $table->unsignedBigInteger('current_version_id')->nullable();
            $table->string('status', 10)->default('active'); // active | retired
            $table->timestamps();
            $table->unique(['organization_id', 'code']);
        });

        Schema::create('qa_document_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_id')->constrained('qa_documents')->cascadeOnDelete();
            $table->unsignedSmallInteger('version');
            $table->string('status', 10)->default('draft'); // draft | in_review | approved | superseded | rejected
            $table->longText('body');
            $table->string('change_note', 500)->nullable();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->string('decision_note', 500)->nullable();
            $table->date('effective_on')->nullable();
            $table->date('review_due_on')->nullable();
            $table->timestamps();
            $table->unique(['document_id', 'version']);
        });

        Schema::create('qa_document_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_id')->constrained('qa_documents')->cascadeOnDelete();
            $table->string('audience', 10); // all | role | department | user
            $table->string('value', 80)->default('');
            $table->timestamps();
            $table->unique(['document_id', 'audience', 'value'], 'qa_doc_assign_unique');
        });

        Schema::create('qa_acknowledgements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_id')->constrained('qa_documents')->cascadeOnDelete();
            $table->foreignId('version_id')->constrained('qa_document_versions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('acknowledged_at');
            $table->unique(['version_id', 'user_id']);
            $table->index(['user_id', 'document_id']);
        });

        // ---- privileging, compliance calendar, accreditation
        Schema::create('qa_privilege_catalog', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('code', 30);
            $table->string('name', 150);
            $table->string('description', 300)->nullable();
            $table->string('requires_credential', 20)->nullable(); // hr_credentials.credential_type the person must hold
            $table->unsignedSmallInteger('validity_months')->default(24);
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique(['organization_id', 'code']);
        });

        Schema::create('qa_privileges', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('employee_id'); // hr_employees.id
            $table->foreignId('catalog_id')->constrained('qa_privilege_catalog')->cascadeOnDelete();
            $table->string('status', 10)->default('requested'); // requested | granted | declined | suspended | revoked | expired
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->date('granted_on')->nullable();
            $table->date('expires_on')->nullable();
            $table->string('note', 500)->nullable();
            $table->timestamps();
            $table->index(['organization_id', 'status', 'expires_on'], 'qa_privileges_status_idx');
            $table->index(['employee_id', 'catalog_id']);
        });

        Schema::create('qa_compliance_items', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('kind', 12); // licence | inspection | certificate | insurance | other
            $table->string('title', 200);
            $table->string('authority', 150)->nullable();
            $table->string('reference', 80)->nullable();
            $table->date('due_on');
            $table->date('last_done_on')->nullable();
            $table->unsignedSmallInteger('interval_months')->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 10)->default('active'); // active | retired
            $table->timestamp('alerted_at')->nullable();
            $table->timestamps();
            $table->index(['organization_id', 'status', 'due_on'], 'qa_compliance_due_idx');
        });

        Schema::create('qa_standards', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('code', 30);
            $table->string('name', 200);
            $table->string('edition', 40)->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique(['organization_id', 'code']);
        });

        Schema::create('qa_standard_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('standard_id')->constrained('qa_standards')->cascadeOnDelete();
            $table->string('code', 30);
            $table->string('chapter', 120)->nullable();
            $table->string('requirement', 600);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->unique(['standard_id', 'code']);
        });

        Schema::create('qa_assessments', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('standard_id')->constrained('qa_standards')->cascadeOnDelete();
            $table->string('name', 200);
            $table->string('status', 12)->default('in_progress'); // planned | in_progress | completed
            $table->date('started_on');
            $table->date('completed_on')->nullable();
            $table->foreignId('lead_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('score', 5, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('qa_assessment_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained('qa_assessments')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('qa_standard_items')->cascadeOnDelete();
            $table->string('result', 14)->default('not_assessed'); // not_assessed | compliant | partial | non_compliant | na
            $table->string('evidence', 1000)->nullable();
            $table->foreignId('action_id')->nullable()->constrained('qa_actions')->nullOnDelete();
            $table->foreignId('assessed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assessed_at')->nullable();
            $table->unique(['assessment_id', 'item_id']);
        });

        // ---- committees and meetings
        Schema::create('qa_committees', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('purpose', 500)->nullable();
            $table->string('kind', 10)->default('safety'); // safety | infection | mm | other
            $table->foreignId('chair_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('qa_committee_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('committee_id')->constrained('qa_committees')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role', 10)->default('member'); // chair | secretary | member
            $table->unique(['committee_id', 'user_id']);
        });

        Schema::create('qa_meetings', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('committee_id')->constrained('qa_committees')->cascadeOnDelete();
            $table->string('title', 200);
            $table->timestamp('scheduled_at');
            $table->timestamp('held_at')->nullable();
            $table->string('location', 120)->nullable();
            $table->text('agenda')->nullable();
            $table->json('attendees')->nullable(); // user ids
            $table->text('minutes')->nullable();
            $table->string('status', 10)->default('planned'); // planned | held | minuted | cancelled
            $table->foreignId('minuted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['organization_id', 'scheduled_at']);
        });

        Schema::create('qa_meeting_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained('qa_meetings')->cascadeOnDelete();
            $table->string('kind', 10); // incident | mortality | topic
            $table->string('ref_type', 20)->nullable();
            $table->unsignedBigInteger('ref_id')->nullable();
            $table->string('title', 250);
            $table->text('discussion')->nullable();
            $table->string('outcome', 1000)->nullable();
            $table->foreignId('action_id')->nullable()->constrained('qa_actions')->nullOnDelete();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->unique(['meeting_id', 'ref_type', 'ref_id'], 'qa_meeting_item_ref_unique');
        });

        $this->protectDocuments();
    }

    /** An approved version's text is never edited: a change is a new version. */
    private function protectDocuments(): void
    {
        $text = 'an approved version cannot be changed: make a new version';
        if (DB::getDriverName() === 'sqlite') {
            DB::unprepared("CREATE TRIGGER qa_versions_frozen BEFORE UPDATE ON qa_document_versions WHEN OLD.status IN ('approved','superseded') AND (OLD.body IS NOT NEW.body OR OLD.version IS NOT NEW.version OR OLD.document_id IS NOT NEW.document_id) BEGIN SELECT RAISE(ABORT, '$text'); END");
            DB::unprepared("CREATE TRIGGER qa_versions_no_delete BEFORE DELETE ON qa_document_versions WHEN OLD.status IN ('approved','superseded') BEGIN SELECT RAISE(ABORT, '$text'); END");

            return;
        }
        DB::unprepared("CREATE TRIGGER qa_versions_frozen BEFORE UPDATE ON qa_document_versions FOR EACH ROW BEGIN IF OLD.status IN ('approved','superseded') AND (NOT (OLD.body <=> NEW.body) OR OLD.version <> NEW.version OR OLD.document_id <> NEW.document_id) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = '$text'; END IF; END");
        DB::unprepared("CREATE TRIGGER qa_versions_no_delete BEFORE DELETE ON qa_document_versions FOR EACH ROW BEGIN IF OLD.status IN ('approved','superseded') THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = '$text'; END IF; END");
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS qa_versions_frozen');
        DB::unprepared('DROP TRIGGER IF EXISTS qa_versions_no_delete');
        foreach (['qa_meeting_items', 'qa_meetings', 'qa_committee_members', 'qa_committees', 'qa_assessment_results', 'qa_assessments', 'qa_standard_items', 'qa_standards', 'qa_compliance_items', 'qa_privileges',
            'qa_privilege_catalog', 'qa_acknowledgements', 'qa_document_assignments', 'qa_document_versions', 'qa_documents', 'qa_mortality_reviews', 'qa_audit_findings', 'qa_audits', 'qa_indicator_values',
            'qa_indicators', 'qa_outbreaks', 'qa_hand_hygiene_audits', 'qa_isolations', 'qa_infection_cases'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
