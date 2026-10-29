<?php

namespace App\Services\Interop;

use App\Interop\Fhir\FhirActor;
use App\Interop\Hl7\Hl7Builder;
use App\Models\Appointment;
use App\Models\Billing\BillingInvoice;
use App\Models\Encounter;
use App\Models\Integration\IntegrationMessage;
use App\Models\Integration\IntegrationPartner;
use App\Models\Integration\IntegrationRoute;
use App\Models\Laboratory\LabOrder;
use App\Models\Laboratory\LabReport;
use App\Models\Laboratory\LabResult;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * From a domain event to messages waiting to be sent (S03, S10). Routing rules say: for this event, send this to that partner, in this format, if these conditions hold. Nothing goes over the
 * network here: a message is only queued (the worker sends it), so a slow or broken partner can never slow down or break the work that raised the event. A patient who has not agreed to share
 * with a partner that needs consent produces no message and a line in the access log.
 */
class EventPublisher
{
    public function __construct(private readonly MessageService $messages, private readonly FhirService $fhir, private readonly ExchangeGuard $guard, private readonly Hl7Builder $hl7, private readonly IdMapper $ids) {}

    /**
     * @param  array<string, mixed>  $attributes  facts the rules can test (for example encounter_type)
     * @return int how many messages were queued
     */
    public function publish(string $eventKey, Model $subject, array $attributes = []): int
    {
        $organizationId = $this->organizationOf($subject);
        $routes = IntegrationRoute::query()->where(['organization_id' => $organizationId, 'event_key' => $eventKey, 'active' => true])->orderBy('priority')->get();
        if ($routes->isEmpty()) {
            return 0;
        }
        $patient = $this->patientOf($subject);
        $queued = 0;
        foreach ($routes as $route) {
            $partner = IntegrationPartner::query()->find($route->partner_id);
            if ($partner === null || $partner->status !== 'active' || ! in_array($partner->direction, ['out', 'both'], true) || ! $this->matches((array) $route->conditions, $attributes)) {
                continue;
            }
            if ($patient !== null && ! $this->guard->check($partner, $patient, class_basename($subject), 'send', $this->ids->expose($subject))) {
                continue;
            }
            try {
                [$type, $channel, $payload] = $this->build($route->format, $eventKey, $subject, $partner);
            } catch (Throwable $e) {
                report($e);

                continue;
            }
            $exposed = $this->ids->expose($subject);
            [, $existing] = $this->messages->enqueue($organizationId, $partner, $channel, $type, $payload, "event:$eventKey:$exposed:route{$route->id}", ['endpoint_id' => $route->endpoint_id, 'event_key' => $eventKey, 'patient_id' => $patient?->id,
                'provenance' => ['route' => $route->name, 'route_id' => $route->id, 'format' => $route->format, 'subject' => class_basename($subject).'/'.$exposed, 'profile' => $partner->profile]]);
            $existing || $queued++;
        }

        return $queued;
    }

    /** @param array<string, mixed> $conditions @param array<string, mixed> $attributes */
    private function matches(array $conditions, array $attributes): bool
    {
        foreach ($conditions as $key => $expected) {
            if (strtolower((string) ($attributes[$key] ?? '')) !== strtolower((string) $expected)) {
                return false;
            }
        }

        return true;
    }

    /** @return array{0:string,1:string,2:string} message type, channel, payload text */
    private function build(string $format, string $eventKey, Model $subject, IntegrationPartner $partner): array
    {
        $actor = new FhirActor($partner->organization_id, null, null, false);
        if ($format === 'json') { // a thin notice: what happened and where to read it; the details are fetched over FHIR with the partner's own token and consent checks
            $body = ['event' => $eventKey, 'occurred_at' => now()->utc()->format('Y-m-d\TH:i:s\Z'), 'subject' => ['type' => class_basename($subject), 'id' => $this->ids->expose($subject)], 'fhir_base' => url('/api/v1/fhir')];

            return ["webhook:$eventKey", 'webhook', json_encode($body, JSON_UNESCAPED_SLASHES)];
        }
        if ($format === 'hl7') {
            $text = match (true) {
                $subject instanceof Patient => $this->hl7->adt('A04', $subject),
                $subject instanceof Encounter => $this->hl7->adt($eventKey === 'encounter.completed' ? 'A03' : 'A01', Patient::query()->findOrFail($subject->patient_id), $subject),
                $subject instanceof LabReport => $this->hl7->oruFromLabReport($subject),
                default => throw new InteropException("No HL7 message is defined for $eventKey.", 422, 'not-supported'),
            };

            return [explode('|', explode("\r", $text)[0])[8] ?? 'HL7', 'hl7', $text];
        }
        $bundle = match (true) {
            $subject instanceof Patient => $this->bundle([$this->fhir->mapper('Patient')->toFhir($subject)]),
            $subject instanceof Encounter => $this->fhir->encounterBundle($actor, $this->ids->expose($subject)),
            $subject instanceof Appointment => $this->bundle([$this->fhir->mapper('Appointment')->toFhir($subject)]),
            $subject instanceof BillingInvoice => $this->bundle([$this->fhir->mapper('Invoice')->toFhir($subject)]),
            $subject instanceof LabReport => $this->bundle(array_merge([$this->fhir->mapper('DiagnosticReport')->toFhir($subject)], $this->labObservations($subject))),
            $subject instanceof Prescription => $this->bundle(PrescriptionItem::query()->where('prescription_id', $subject->id)->get()->map(fn ($i) => $this->fhir->mapper('MedicationRequest')->toFhir($i))->all()),
            default => throw new InteropException("No FHIR bundle is defined for $eventKey.", 422, 'not-supported'),
        };

        return ['Bundle', 'fhir', json_encode($bundle, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)];
    }

    /** @return list<array<string,mixed>> */
    private function labObservations(LabReport $r): array
    {
        $items = DB::table('lab_order_items')->where('lab_order_id', $r->lab_order_id)->pluck('id');
        $mapper = $this->fhir->mapper('Observation');

        return LabResult::query()->whereIn('lab_order_item_id', $items)->where('is_current', true)->get()->map(fn ($x) => $mapper->toFhir($x))->all();
    }

    /** @param list<array<string,mixed>> $resources */
    private function bundle(array $resources): array
    {
        return ['resourceType' => 'Bundle', 'type' => 'collection', 'timestamp' => now()->utc()->format('Y-m-d\TH:i:s\Z'), 'entry' => array_map(fn ($r) => ['fullUrl' => url('/api/v1/fhir').'/'.$r['resourceType'].'/'.$r['id'], 'resource' => $r], $resources)];
    }

    private function organizationOf(Model $m): int
    {
        return (int) ($m->getAttribute('organization_id') ?? Patient::query()->whereKey($m->getAttribute('patient_id'))->value('organization_id'));
    }

    private function patientOf(Model $m): ?Patient
    {
        if ($m instanceof Patient) {
            return $m;
        }
        $pid = $m->getAttribute('patient_id') ?? ($m instanceof LabReport ? LabOrder::query()->whereKey($m->lab_order_id)->value('patient_id') : null);

        return $pid ? Patient::query()->find($pid) : null;
    }

    /** A person sends the current state of a record to a partner right now (a test send, or a re-send), without waiting for an event. */
    public function sendNow(IntegrationPartner $partner, string $format, Model $subject, string $eventKey = 'manual'): IntegrationMessage
    {
        [$type, $channel, $payload] = $this->build($format, $eventKey, $subject, $partner);
        [$m] = $this->messages->enqueue($partner->organization_id, $partner, $channel, $type, $payload, 'manual:'.Str::ulid(), ['event_key' => $eventKey, 'patient_id' => $this->patientOf($subject)?->id, 'provenance' => ['manual' => true, 'subject' => class_basename($subject).'/'.$this->ids->expose($subject)]]);

        return $m;
    }
}
