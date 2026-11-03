<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Phase 17, part 3: CRM — interactions, segments, campaigns, feedback, complaints, digital forms (S11). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_interactions', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('patient_id')->nullable()->constrained()->nullOnDelete();
            $table->string('contact_name', 120)->nullable();
            $table->string('contact_phone', 20)->nullable();
            $table->string('channel', 10);
            $table->string('direction', 3)->default('in'); // in | out
            $table->string('subject', 160);
            $table->text('summary')->nullable();
            $table->string('outcome', 160)->nullable();
            $table->string('status', 8)->default('open'); // open | done
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->date('follow_up_on')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['organization_id', 'status', 'follow_up_on']);
            $table->index(['patient_id', 'created_at']);
        });

        Schema::create('crm_segments', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('description', 300)->nullable();
            $table->json('criteria');
            $table->string('clinical_basis', 300)->nullable(); // documented legal basis; required when the criteria use a diagnosis
            $table->string('status', 8)->default('draft'); // draft | active | retired
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedInteger('last_count')->nullable();
            $table->timestamp('last_counted_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['organization_id', 'name']);
        });

        Schema::create('crm_campaigns', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('purpose', 10); // service | marketing
            $table->string('channel', 8);
            $table->foreignId('segment_id')->constrained('crm_segments')->restrictOnDelete();
            $table->string('subject', 160)->nullable();
            $table->text('body');
            $table->string('status', 12)->default('draft'); // draft | pending | approved | running | completed | cancelled
            $table->timestamp('scheduled_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('stats')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
        });

        Schema::create('crm_campaign_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('crm_campaigns')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('status', 10); // queued | deferred | sent | suppressed | failed
            $table->string('reason', 120)->nullable();
            $table->unsignedBigInteger('outbox_id')->nullable();
            $table->timestamps();

            $table->unique(['campaign_id', 'patient_id']);
        });

        Schema::create('crm_feedback', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('patient_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->foreignId('provider_id')->nullable()->constrained('providers')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('channel', 10)->default('portal');
            $table->unsignedTinyInteger('rating'); // 1..5
            $table->unsignedTinyInteger('nps')->nullable(); // 0..10
            $table->string('comment', 1000)->nullable();
            $table->string('status', 10)->default('new'); // new | reviewed
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['patient_id', 'appointment_id']);
            $table->index(['organization_id', 'created_at']);
        });

        Schema::create('crm_complaints', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('patient_id')->nullable()->constrained()->nullOnDelete();
            $table->string('number', 30);
            $table->string('channel', 10)->default('portal');
            $table->string('category', 20);
            $table->string('severity', 8)->default('medium'); // low | medium | high
            $table->text('description');
            $table->string('status', 14)->default('open'); // open | assigned | investigating | resolved | closed | escalated
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->string('resolution', 500)->nullable();
            $table->unsignedTinyInteger('satisfaction')->nullable(); // 1..5, asked after resolution
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'number']);
            $table->index(['organization_id', 'status', 'due_at']);
        });

        Schema::create('crm_complaint_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('complaint_id')->constrained('crm_complaints')->cascadeOnDelete();
            $table->string('kind', 14);
            $table->string('note', 500)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('crm_forms', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('code', 40);
            $table->string('title', 150);
            $table->json('fields'); // [{key,label,type:text|textarea|select|yesno|number|date,required,options[]}]
            $table->unsignedSmallInteger('version')->default(1);
            $table->string('status', 8)->default('active'); // active | retired
            $table->timestamps();

            $table->unique(['organization_id', 'code']);
        });

        Schema::create('crm_form_submissions', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('form_id')->constrained('crm_forms')->cascadeOnDelete();
            $table->unsignedSmallInteger('form_version');
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->nullable()->constrained('patient_portal_accounts')->nullOnDelete();
            $table->text('answers'); // encrypted json (health information)
            $table->string('status', 10)->default('submitted'); // submitted | reviewed
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
        });
    }

    public function down(): void
    {
        foreach (['crm_form_submissions', 'crm_forms', 'crm_complaint_events', 'crm_complaints', 'crm_feedback', 'crm_campaign_recipients', 'crm_campaigns', 'crm_segments', 'crm_interactions'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
