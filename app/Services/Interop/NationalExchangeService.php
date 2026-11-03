<?php

namespace App\Services\Interop;

use App\Interop\Fhir\FhirActor;
use App\Models\Integration\IntegrationMessage;
use App\Models\Integration\IntegrationPartner;
use App\Models\Patient;
use App\Models\User;
use App\Services\AuditLogger;

/** Sharing a patient's record with the national exchange (guideline override 6): a FHIR Bundle goes into the queue for the worker to hand to the exchange gateway. Consent first; an emergency override needs a reason and is logged. */
class NationalExchangeService
{
    public function __construct(private readonly FhirService $fhir, private readonly MessageService $messages, private readonly ExchangeGuard $guard, private readonly AuditLogger $audit, private readonly IdMapper $ids) {}

    public function share(IntegrationPartner $exchange, Patient $patient, User $by, ?string $overrideReason = null): IntegrationMessage
    {
        if ($exchange->kind !== 'national_exchange' || $exchange->status !== 'active') {
            throw new InteropException('The national exchange partner is not set up and active.', 409, 'conflict');
        }
        if (! $this->guard->check($exchange, $patient, 'Patient', 'send', $this->ids->expose($patient), $by, $overrideReason)) {
            throw new InteropException('This patient has not agreed to share their record with the national exchange.', 403, 'forbidden');
        }
        // build the bundle as the hospital itself (not as the partner), because the consent was just checked
        $bundle = $this->fhir->everything(new FhirActor($patient->organization_id, null, $by, false), $this->ids->expose($patient));
        $endpoint = $exchange->endpoints()->where(['transport' => 'national', 'active' => true])->first();
        [$m] = $this->messages->enqueue($patient->organization_id, $exchange, 'national', 'Bundle', json_encode($bundle, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 'national:'.$this->ids->expose($patient).':'.now()->format('YmdHis'),
            ['endpoint_id' => $endpoint?->id, 'patient_id' => $patient->id, 'event_key' => 'national.share', 'provenance' => ['by' => $by->id, 'override' => $overrideReason !== null && $overrideReason !== '']]);
        $this->audit->log('CREATE', IntegrationMessage::class, $m->id, null, ['national_share' => $patient->id, 'by' => $by->id]);

        return $m;
    }
}
