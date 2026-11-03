<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Phase 18, part 1: partners, credentials, endpoints, routing rules, the message queue with attempts, identifier maps, replay-protection nonces and the exchange access log (S01–S04, S10, S11). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_partners', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('code', 40);
            $table->string('name', 150);
            $table->string('kind', 20); // fhir_client | fhir_server | hl7_peer | dicom_node | webhook | national_exchange
            $table->string('direction', 5)->default('both'); // in | out | both
            $table->string('status', 8)->default('draft'); // draft | active | paused | retired
            $table->string('profile', 20)->default('r4');
            $table->string('consent_type', 40)->nullable(); // patient consent needed before patient data goes to / comes from this partner
            $table->boolean('requires_consent')->default(true);
            $table->string('ae_title', 16)->nullable();
            $table->string('contact', 200)->nullable();
            $table->text('notes')->nullable();
            $table->json('settings')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['organization_id', 'code']);
        });

        Schema::create('integration_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained('integration_partners')->cascadeOnDelete();
            $table->string('label', 40); // e.g. outbound-api, webhook-signing
            $table->string('kind', 12); // api_key | bearer | basic | hmac_secret | client_cert
            $table->string('key_id', 80)->nullable();
            $table->text('secret'); // encrypted
            $table->unsignedSmallInteger('version')->default(1);
            $table->string('status', 9)->default('active'); // active | retiring | revoked
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_to')->nullable(); // a retiring version still works until then (rotation without a gap)
            $table->timestamp('last_used_at')->nullable();
            $table->foreignId('rotated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['partner_id', 'label', 'version']);
        });

        Schema::create('integration_endpoints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained('integration_partners')->cascadeOnDelete();
            $table->string('name', 80);
            $table->string('transport', 10); // https | mllp | dicomweb | national
            $table->string('url', 300); // https url, or host:port for mllp
            $table->string('format', 8)->default('fhir'); // fhir | hl7 | json
            $table->unsignedSmallInteger('timeout_seconds')->default(10);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('integration_routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('event_key', 40);
            $table->foreignId('partner_id')->constrained('integration_partners')->cascadeOnDelete();
            $table->foreignId('endpoint_id')->nullable()->constrained('integration_endpoints')->nullOnDelete();
            $table->string('format', 8)->default('fhir'); // fhir | hl7 | json
            $table->json('conditions')->nullable(); // all must hold, e.g. {"encounter_type": "opd"}
            $table->unsignedSmallInteger('priority')->default(100);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['organization_id', 'event_key', 'active']);
        });

        Schema::create('integration_messages', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('partner_id')->nullable()->constrained('integration_partners')->nullOnDelete();
            $table->foreignId('endpoint_id')->nullable()->constrained('integration_endpoints')->nullOnDelete();
            $table->string('direction', 3); // in | out
            $table->string('channel', 10); // fhir | hl7 | dicom | webhook | national
            $table->string('message_type', 40);
            $table->string('event_key', 40)->nullable();
            $table->foreignId('patient_id')->nullable()->constrained()->nullOnDelete();
            $table->string('idempotency_key', 190);
            $table->string('status', 10)->default('queued'); // received | queued | sending | processed | retrying | dead | dismissed | ignored | rejected
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamp('next_attempt_at')->nullable();
            $table->longText('payload'); // encrypted: it holds patient data
            $table->char('payload_hash', 64);
            $table->text('response')->nullable(); // encrypted
            $table->string('error', 500)->nullable();
            $table->string('correlation_id', 60)->nullable();
            $table->foreignId('replay_of_id')->nullable()->constrained('integration_messages')->nullOnDelete();
            $table->json('provenance')->nullable(); // where it came from and which rule and mapper produced it
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('dead_at')->nullable();
            $table->timestamp('purged_at')->nullable(); // the payload was cleared by the retention rule; the record of what happened stays
            $table->timestamps();

            $table->unique(['organization_id', 'direction', 'idempotency_key'], 'integration_msg_idem_unique');
            $table->index(['status', 'next_attempt_at']);
            $table->index(['organization_id', 'status', 'created_at']);
            $table->index(['patient_id', 'created_at']);
        });

        Schema::create('integration_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained('integration_messages')->cascadeOnDelete();
            $table->unsignedSmallInteger('attempt_no');
            $table->string('outcome', 6); // ok | retry | fail
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->string('detail', 500)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
        });

        Schema::create('integration_id_maps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('scope', 40); // 'local' = the opaque id WE show outside; otherwise a partner code = that partner's own id
            $table->string('entity_type', 40);
            $table->unsignedBigInteger('local_id');
            $table->string('external_id', 120);
            $table->timestamps();

            $table->unique(['organization_id', 'scope', 'entity_type', 'local_id'], 'integration_idmap_local');
            $table->unique(['organization_id', 'scope', 'entity_type', 'external_id'], 'integration_idmap_external');
        });

        Schema::create('integration_nonces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained('integration_partners')->cascadeOnDelete();
            $table->string('nonce', 120);
            $table->timestamp('seen_at');

            $table->unique(['partner_id', 'nonce']);
        });

        // Append-only: who was shown or sent what about which patient, and what was refused.
        Schema::create('integration_access_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('partner_id')->nullable()->constrained('integration_partners')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('patient_id')->nullable()->constrained()->nullOnDelete();
            $table->string('resource_type', 40)->nullable();
            $table->string('resource_id', 60)->nullable();
            $table->string('action', 12); // read | search | create | update | operation | send | receive
            $table->string('outcome', 16); // allowed | denied_consent | denied_scope | override | error
            $table->string('reason', 200)->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['patient_id', 'created_at']);
            $table->index(['partner_id', 'created_at']);
            $table->index(['organization_id', 'outcome', 'created_at']);
        });
    }

    public function down(): void
    {
        foreach (['integration_access_log', 'integration_nonces', 'integration_id_maps', 'integration_attempts', 'integration_messages', 'integration_routes', 'integration_endpoints', 'integration_credentials', 'integration_partners'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
