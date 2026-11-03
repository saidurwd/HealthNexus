<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/** Phase 17, part 1: the portal account (extends the Phase 1 table), OTPs, devices, security events, proxies, communication preferences, outbox, notification centre, access log, shared documents (S01, S02, S07, S08, S10). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patient_portal_accounts', function (Blueprint $table) {
            $table->char('public_id', 26)->nullable()->unique();
            $table->string('phone', 20)->nullable()->index(); // digits only, country code first
            $table->string('password')->nullable();
            $table->string('status', 10)->default('invited'); // invited | pending | active | locked | suspended | closed
            $table->unsignedSmallInteger('failed_attempts')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            $table->boolean('mfa_required')->default(false);
            $table->string('language', 5)->default('bn');
            $table->string('invitation_hash', 64)->nullable()->index();
            $table->timestamp('invitation_expires_at')->nullable();
            $table->timestamp('terms_accepted_at')->nullable();
            $table->string('terms_version', 20)->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->string('closure_reason', 200)->nullable();
            $table->rememberToken();
        });

        foreach (DB::table('patient_portal_accounts')->whereNull('public_id')->pluck('id') as $id) {
            DB::table('patient_portal_accounts')->where('id', $id)->update(['public_id' => (string) Str::ulid(), 'status' => 'invited']);
        }

        Schema::create('portal_otps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('target', 30);
            $table->string('purpose', 14); // register | login | reset | payment
            $table->foreignId('account_id')->nullable()->constrained('patient_portal_accounts')->cascadeOnDelete();
            $table->string('code_hash', 64);
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['target', 'purpose', 'created_at']);
        });

        Schema::create('portal_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('patient_portal_accounts')->cascadeOnDelete();
            $table->string('device_hash', 64);
            $table->string('name', 100)->nullable();
            $table->string('platform', 12)->default('web'); // web | android | ios
            $table->text('push_token')->nullable();
            $table->timestamp('trusted_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->string('last_ip', 45)->nullable();
            $table->unsignedBigInteger('token_id')->nullable(); // personal_access_tokens.id of the current token
            $table->timestamp('revoked_at')->nullable();
            $table->string('revoked_reason', 100)->nullable();
            $table->timestamps();

            $table->unique(['account_id', 'device_hash']);
        });

        Schema::create('portal_security_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('account_id')->nullable()->constrained('patient_portal_accounts')->nullOnDelete();
            $table->string('kind', 30); // register_start | register_fail | otp_sent | otp_fail | login_ok | login_fail | locked | new_device | token_revoked | rate_limited | password_reset
            $table->string('phone_hash', 64)->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 200)->nullable();
            $table->json('detail')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['kind', 'phone_hash', 'created_at']);
            $table->index(['kind', 'ip', 'created_at']);
        });

        Schema::create('portal_proxies', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('patient_portal_accounts')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('relationship', 14);
            $table->json('scopes');
            $table->string('status', 10)->default('requested'); // requested | active | rejected | revoked | expired
            $table->string('evidence', 300)->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->string('decision_note', 300)->nullable();
            $table->timestamps();

            $table->index(['account_id', 'status']);
            $table->index(['patient_id', 'status']);
        });

        Schema::create('portal_comm_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('channel', 8);
            $table->string('purpose', 14);
            $table->boolean('opted_in');
            $table->string('source', 10)->default('portal'); // portal | staff | import | stop_word
            $table->timestamp('changed_at')->nullable();
            $table->timestamps();

            $table->unique(['patient_id', 'channel', 'purpose']);
        });

        Schema::create('portal_outbox', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->nullable()->constrained()->nullOnDelete();
            $table->string('channel', 8);
            $table->string('purpose', 14);
            $table->string('template', 60);
            $table->string('to', 190)->nullable();
            $table->string('subject', 190)->nullable();
            $table->text('body');
            $table->string('status', 10)->default('queued'); // queued | deferred | sent | failed | suppressed
            $table->string('reason', 120)->nullable();
            $table->timestamp('send_after')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->unsignedBigInteger('campaign_id')->nullable()->index();
            $table->timestamps();

            $table->index(['status', 'send_after']);
            $table->index(['patient_id', 'channel', 'purpose', 'created_at'], 'portal_outbox_patient_idx');
        });

        Schema::create('portal_notifications', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 30); // appointment | result | payment | prescription | message | document | consent | announcement
            $table->string('title', 160);
            $table->string('body', 400)->nullable();
            $table->string('link', 200)->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'read_at']);
        });

        Schema::create('portal_access_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->nullable()->constrained('patient_portal_accounts')->nullOnDelete();
            $table->foreignId('patient_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('as_proxy')->default(false);
            $table->string('action', 30); // view_result | view_report | download_document | view_invoice | view_record | share_link
            $table->string('subject_type', 40)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['patient_id', 'created_at']);
            $table->index(['account_id', 'created_at']);
        });

        Schema::create('portal_documents', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('title', 160);
            $table->string('source_type', 24); // lab_report | radiology_report | receipt | discharge_summary | prescription
            $table->unsignedBigInteger('source_id');
            $table->char('token_hash', 64)->unique();
            $table->timestamp('expires_at');
            $table->unsignedSmallInteger('max_downloads')->nullable();
            $table->unsignedSmallInteger('downloads')->default(0);
            $table->foreignId('created_by_account_id')->nullable()->constrained('patient_portal_accounts')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        foreach (['portal_documents', 'portal_access_log', 'portal_notifications', 'portal_outbox', 'portal_comm_preferences', 'portal_proxies', 'portal_security_events', 'portal_devices', 'portal_otps'] as $t) {
            Schema::dropIfExists($t);
        }
        Schema::table('patient_portal_accounts', function (Blueprint $table) {
            $table->dropColumn(['public_id', 'phone', 'password', 'status', 'failed_attempts', 'locked_until', 'last_login_at', 'last_login_ip', 'mfa_required', 'language', 'invitation_hash', 'invitation_expires_at', 'terms_accepted_at', 'terms_version', 'closed_at', 'closure_reason', 'remember_token']);
        });
    }
};
