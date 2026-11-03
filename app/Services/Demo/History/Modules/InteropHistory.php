<?php

namespace App\Services\Demo\History\Modules;

use App\Interop\Fhir\FhirActor;
use App\Interop\Hl7\Hl7Inbound;
use App\Interop\Transports\Transport;
use App\Interop\Transports\TransportResult;
use App\Models\Billing\BillingInvoice;
use App\Models\Encounter;
use App\Models\Integration\DicomWorklistEntry;
use App\Models\Integration\Hl7InboundResult;
use App\Models\Integration\IntegrationEndpoint;
use App\Models\Integration\IntegrationMessage;
use App\Models\Integration\IntegrationPartner;
use App\Models\Integration\IntegrationRoute;
use App\Models\Laboratory\LabReport;
use App\Models\Patient;
use App\Models\PatientConsent;
use App\Models\Radiology\RadiologyExamination;
use App\Services\Demo\History\DemoHistoryModule;
use App\Services\Demo\History\HistoryContext;
use App\Services\Interop\CredentialService;
use App\Services\Interop\DeliveryWorker;
use App\Services\Interop\DicomWorklistService;
use App\Services\Interop\EventPublisher;
use App\Services\Interop\FhirService;
use App\Services\Interop\IdMapper;
use App\Services\Interop\InteropException;
use App\Services\Interop\MessageService;
use App\Services\Interop\NationalExchangeService;
use Carbon\CarbonImmutable;

/**
 * Connections to other systems, six months. Five partners are set up (a reference laboratory over HL7, a partner hospital over FHIR, the national exchange test client, a scanner and an insurer notice feed),
 * with secrets (one rotated) and rules that say what goes where. About four in ten patients agree to share; for them every registration, every finished inpatient visit and every final lab report is queued, sent,
 * retried when the partner is down for three days, and the unlucky ones dead-lettered — half sent again by a person, half set aside. The laboratory sends admissions, updates and results (some for unknown patients,
 * some twice); results wait for a person to review. The partner hospital reads records through the FHIR API; those who did not agree are refused and logged. Radiology exams appear on the scanner worklist.
 * Some records are shared with the national exchange. Everything goes through the module's own services; only the network is pretended.
 */
class InteropHistory implements DemoHistoryModule
{
    private const FIRST = ['Anika', 'Bashir', 'Chaity', 'Dilruba', 'Emon', 'Farida', 'Gazi', 'Humayun', 'Ismat', 'Jahanara', 'Kabir', 'Lutfa', 'Monir', 'Nilufar', 'Omar', 'Parveen'];

    private const LAST = ['Alam', 'Barua', 'Chakma', 'Dewan', 'Ershad', 'Faruque', 'Gomes', 'Haque', 'Imam', 'Jalil', 'Kazi', 'Lodhi'];

    /** @var array<string, int> */
    private array $summary = ['partners' => 0, 'consents' => 0, 'queued' => 0, 'inbound' => 0, 'reads' => 0, 'refused' => 0, 'shared' => 0, 'worklist' => 0, 'replayed' => 0, 'dismissed' => 0];

    /** the window in which the partner hospital's server is down */
    private ?array $outage = null;

    public function __construct(
        private readonly CredentialService $credentials,
        private readonly EventPublisher $publisher,
        private readonly DeliveryWorker $worker,
        private readonly MessageService $messages,
        private readonly Hl7Inbound $hl7,
        private readonly FhirService $fhir,
        private readonly DicomWorklistService $dicom,
        private readonly NationalExchangeService $national,
        private readonly IdMapper $ids,
    ) {}

    public function name(): string
    {
        return 'Interoperability';
    }

    public function module(): string
    {
        return 'interop';
    }

    public function run(HistoryContext $context): string
    {
        config(['interop.national.driver' => 'sandbox']); // a pretend national exchange; nothing leaves this system
        $org = $context->organization->id;
        [$hie, $lab, $national, $scanner, $insurer] = $this->setUp($context);
        $this->worker->useTransport('https', $this->transport());
        $this->worker->useTransport('mllp', $this->transport());

        $patients = $context->patients();
        $consenting = [];
        $sharedWithNational = 0;
        $days = iterator_to_array($context->days());
        $this->outage = [$days[(int) (count($days) * 0.55)] ?? $days[0], ($days[(int) (count($days) * 0.55) + 2] ?? $days[0])->endOfDay()];
        $registeredOn = $patients->groupBy(fn (Patient $p) => ($p->registered_at ?? $p->created_at)?->toDateString());

        foreach ($days as $i => $day) {
            $date = $day->toDateString();
            $context->at($day->setTime(5, 0), function () use ($registeredOn, $date, &$consenting, $context) {
                foreach ($registeredOn[$date] ?? [] as $patient) {
                    if ($context->chance(40)) {
                        $this->grant($patient, 'data_sharing');
                        $consenting[$patient->id] = $patient;
                    }
                }
            });
            // evening: what happened today is queued for the partners, the sender runs, inbound traffic arrives
            $context->at($day->setTime(12, 0), function () use ($org, $date, $registeredOn, $context, $hie, $lab, &$consenting) {
                foreach ($registeredOn[$date] ?? [] as $patient) {
                    $this->summary['queued'] += $this->publisher->publish('patient.registered', $patient);
                }
                Encounter::query()->where('organization_id', $org)->whereDate('encounter_date', $date)->whereIn('encounter_type', ['IPD', 'ipd', 'Inpatient'])->whereIn('status', ['completed', 'locked'])->each(fn (Encounter $e) => $this->summary['queued'] += $this->publisher->publish('encounter.completed', $e, ['encounter_type' => 'IPD']));
                LabReport::query()->where('organization_id', $org)->whereIn('status', ['final', 'amended'])->whereDate('generated_at', $date)->each(fn (LabReport $r) => $this->summary['queued'] += $this->publisher->publish('lab.report.finalized', $r));
                BillingInvoice::query()->where('organization_id', $org)->whereDate('finalized_at', $date)->limit(40)->each(fn (BillingInvoice $inv) => $this->summary['queued'] += $this->publisher->publish('invoice.finalized', $inv));
                $this->inbound($context, $lab, $date);
                if ($context->chance(60) && $consenting !== []) {
                    $this->partnerReads($context, $hie, $consenting);
                }
                $this->worker->run(400);
            });
            // the retry waits are minutes to hours: run the sender again late in the evening and the next morning
            $context->at($day->setTime(16, 0), fn () => $this->worker->run(400));
            if ($i % 7 === 3) {
                $context->at($day->setTime(13, 0), fn () => $this->insurerNotice($insurer, $date));
            }
        }

        $end = $context->to->setTime(14, 0);
        $context->at($end, function () use ($context, $national, $scanner, $consenting, $org) {
            $this->worklist($org, $scanner);
            $this->nationalShares($context, $national, $consenting);
            $this->outageClean($context);
            $this->worker->run(1000);
        });

        return sprintf('%d partners, %d patients agreed to share, %d messages queued (%d replayed after the outage, %d set aside), %d messages received (%d results to review), %d records read by the partner hospital (%d refused for lack of consent), %d scanner worklist entries, %d records shared with the national test exchange',
            $this->summary['partners'], $this->summary['consents'], $this->summary['queued'], $this->summary['replayed'], $this->summary['dismissed'], $this->summary['inbound'], Hl7InboundResult::query()->where(['organization_id' => $org, 'status' => 'new'])->count(), $this->summary['reads'], $this->summary['refused'], $this->summary['worklist'], $this->summary['shared']);
    }

    // ---- set-up ---------------------------------------------------------------------------------------------------------------------------------

    /** @return array{0:IntegrationPartner,1:IntegrationPartner,2:IntegrationPartner,3:IntegrationPartner,4:IntegrationPartner} */
    private function setUp(HistoryContext $context): array
    {
        $org = $context->organization->id;
        $make = function (array $a) use ($org) {
            $this->summary['partners']++;

            return IntegrationPartner::query()->updateOrCreate(['organization_id' => $org, 'code' => $a['code']], $a + ['organization_id' => $org, 'created_by' => null]);
        };
        $hie = $make(['code' => 'CITYHOSP', 'name' => 'City Hospital (partner)', 'kind' => 'fhir_client', 'direction' => 'both', 'status' => 'active', 'profile' => 'bd-core', 'requires_consent' => true, 'consent_type' => 'data_sharing']);
        $lab = $make(['code' => 'REFLAB', 'name' => 'Dhaka Reference Laboratory', 'kind' => 'hl7_peer', 'direction' => 'both', 'status' => 'active', 'profile' => 'r4', 'requires_consent' => true, 'consent_type' => 'data_sharing',
            'settings' => ['sending_application' => 'REFLAB', 'sending_facility' => 'DRL']]);
        $national = $make(['code' => 'NHIE', 'name' => 'National Health Exchange (test connection)', 'kind' => 'national_exchange', 'direction' => 'out', 'status' => 'active', 'profile' => 'bd-core', 'requires_consent' => true, 'consent_type' => 'national_sharing']);
        $scanner = $make(['code' => 'XRAYPC', 'name' => 'Radiology scanner node', 'kind' => 'dicom_node', 'direction' => 'both', 'status' => 'active', 'profile' => 'r4', 'requires_consent' => false, 'ae_title' => 'HNWORKLIST']);
        $insurer = $make(['code' => 'SHIELD', 'name' => 'Shield Insurance notices', 'kind' => 'webhook', 'direction' => 'both', 'status' => 'active', 'profile' => 'r4', 'requires_consent' => false]);
        $make(['code' => 'OLDPACS', 'name' => 'Old archive (not connected yet)', 'kind' => 'fhir_server', 'direction' => 'out', 'status' => 'draft', 'profile' => 'r4', 'requires_consent' => true, 'consent_type' => 'data_sharing']);

        foreach ([[$hie, 'https://api.cityhospital.example/fhir', 'fhir'], [$lab, '10.0.4.20:2575', 'hl7'], [$national, 'national', 'fhir'], [$insurer, 'https://notices.shield.example/hooks', 'json']] as [$p, $url, $format]) {
            IntegrationEndpoint::query()->firstOrCreate(['partner_id' => $p->id, 'name' => 'main'], ['transport' => $p === $lab ? 'mllp' : ($p === $national ? 'national' : 'https'), 'url' => $url, 'format' => $format, 'timeout_seconds' => 10, 'active' => true]);
        }
        $this->credentials->create($hie, 'outbound-api', 'bearer');
        $this->credentials->create($insurer, 'webhook-signing', 'hmac_secret');
        $this->credentials->rotate($insurer, 'webhook-signing', null, 24);
        $this->credentials->issueToken($hie, ['fhir.read', 'fhir.write']);
        $this->credentials->issueToken($scanner, ['dicom.read', 'dicom.write']);

        $rules = [['patient.registered', $hie, 'fhir', null], ['patient.registered', $lab, 'hl7', null], ['encounter.completed', $hie, 'fhir', ['encounter_type' => 'IPD']], ['lab.report.finalized', $hie, 'fhir', null], ['invoice.finalized', $insurer, 'json', null]];
        foreach ($rules as $n => [$event, $partner, $format, $cond]) {
            IntegrationRoute::query()->firstOrCreate(['organization_id' => $org, 'event_key' => $event, 'partner_id' => $partner->id], ['name' => "$event → {$partner->code}", 'format' => $format, 'conditions' => $cond, 'priority' => 100 + $n, 'active' => true,
                'endpoint_id' => IntegrationEndpoint::query()->where('partner_id', $partner->id)->value('id')]);
        }

        return [$hie, $lab, $national, $scanner, $insurer];
    }

    /** The pretend network: usually fine; down for three days at the partner hospital; now and then a hiccup or a refusal. */
    private function transport(): Transport
    {
        return new class($this) implements Transport
        {
            public function __construct(private InteropHistory $history) {}

            public function send(IntegrationMessage $message, IntegrationEndpoint $endpoint, IntegrationPartner $partner): TransportResult
            {
                return $this->history->network($message, $partner);
            }
        };
    }

    public function network(IntegrationMessage $message, IntegrationPartner $partner): TransportResult
    {
        if ($partner->code === 'CITYHOSP' && $this->outage && now()->between($this->outage[0], $this->outage[1])) {
            return TransportResult::retry('HTTP 503', 503);
        }
        $roll = mt_rand(1, 1000);

        return match (true) {
            $roll <= 20 => TransportResult::retry('HTTP 502', 502),
            $roll <= 24 => TransportResult::refused('HTTP 422 (the other side did not accept the content)', 422),
            default => TransportResult::ok(200, $partner->kind === 'hl7_peer' ? 'accepted (AA)' : 'HTTP 200'),
        };
    }

    // ---- traffic ----------------------------------------------------------------------------------------------------------------------------------

    private function grant(Patient $patient, string $type): void
    {
        PatientConsent::query()->firstOrCreate(['patient_id' => $patient->id, 'consent_type' => $type, 'status' => 'active'], ['organization_id' => $patient->organization_id, 'version' => 1, 'granted_at' => now(), 'notes' => 'Demo consent']);
        $type === 'data_sharing' && $this->summary['consents']++;
    }

    private function inbound(HistoryContext $context, IntegrationPartner $lab, string $date): void
    {
        $n = mt_rand(0, 3);
        for ($i = 0; $i < $n; $i++) {
            $first = self::FIRST[mt_rand(0, count(self::FIRST) - 1)];
            $last = self::LAST[mt_rand(0, count(self::LAST) - 1)];
            $dob = CarbonImmutable::parse($date)->subYears(mt_rand(20, 70))->subDays(mt_rand(0, 300))->format('Ymd');
            $control = 'D'.str_replace('-', '', $date).$i.mt_rand(100, 999);
            $pid = 'PID|1||LAB-'.mt_rand(10000, 99999)."^^^DRL^MR||$last^$first||$dob|".(mt_rand(0, 1) ? 'F' : 'M').'|||Road '.mt_rand(1, 40).'^^Dhaka||017'.mt_rand(10000000, 99999999);
            $kind = mt_rand(1, 100);
            $text = match (true) {
                $kind <= 45 => "MSH|^~\\&|REFLAB|DRL|HN|HOSP|{$dob}||ADT^A04^ADT_A01|$control|P|2.5.1\rEVN|A04\r$pid\r",
                $kind <= 60 => "MSH|^~\\&|REFLAB|DRL|HN|HOSP|{$dob}||ADT^A01^ADT_A01|$control|P|2.5.1\rEVN|A01\r$pid\rPV1|1|O|||||^Dr Demo||||||||||||V-".mt_rand(1000, 9999)."\r",
                default => $this->oru($control, $first, $last, $dob),
            };
            $this->hl7->handle($text);
            $this->summary['inbound']++;
            if ($context->chance(8)) { // a sender repeats itself: acknowledged, done once
                $this->hl7->handle($text);
            }
        }
        // a person reviews what arrived last week
        Hl7InboundResult::query()->where(['organization_id' => $context->organization->id, 'status' => 'new'])->where('created_at', '<', now()->subDays(7))->limit(30)->get()->each(fn (Hl7InboundResult $r) => $r->update(['status' => $context->chance(90) ? 'accepted' : 'rejected', 'reviewed_by' => $context->actor->id, 'reviewed_at' => now(), 'note' => 'Demo review']));
    }

    private function oru(string $control, string $first, string $last, string $dob): string
    {
        $known = Patient::query()->whereNotNull('date_of_birth')->inRandomOrder()->first();
        $pid = $known && mt_rand(1, 100) <= 70 ? "PID|1||{$known->enterprise_patient_no}^^^HN^MR||{$known->last_name}^{$known->first_name}||{$known->date_of_birth->format('Ymd')}|F" : 'PID|1||GHOST-'.mt_rand(1000, 9999)."^^^DRL^MR||$last^$first||$dob|M";
        $hb = number_format(mt_rand(70, 170) / 10, 1);

        return "MSH|^~\\&|REFLAB|DRL|HN|HOSP|{$dob}||ORU^R01^ORU_R01|$control|P|2.5.1\r$pid\rOBR|1|P".mt_rand(1, 999).'|F'.mt_rand(1, 999)."|HB^Hemoglobin\rOBX|1|NM|HB^Hemoglobin||$hb|g/dL|12-16|".($hb < 12 ? 'L' : 'N')."|||F\rOBX|2|NM|WBC^White cells||".number_format(mt_rand(30, 140) / 10, 1)."|10*9/L|4-11|N|||F\r";
    }

    /** The partner hospital reads what it may and is refused what it may not. */
    private function partnerReads(HistoryContext $context, IntegrationPartner $hie, array $consenting): void
    {
        $actor = new FhirActor($hie->organization_id, $hie, null, false);
        $pool = Patient::query()->where('organization_id', $hie->organization_id)->inRandomOrder()->limit(4)->get();
        foreach ($pool as $p) {
            try {
                $this->fhir->read($actor, 'Patient', $this->ids->expose($p));
                $this->summary['reads']++;
                $context->chance(50) && $this->fhir->everything($actor, $this->ids->expose($p));
            } catch (InteropException) {
                $this->summary['refused']++;
            }
        }
    }

    private function insurerNotice(IntegrationPartner $insurer, string $date): void
    {
        $body = json_encode(['event' => 'claim.status', 'claim' => 'CLM-'.mt_rand(1000, 9999), 'status' => ['accepted', 'queried', 'paid'][mt_rand(0, 2)], 'date' => $date]);
        $this->messages->receive($insurer->organization_id, $insurer, 'webhook', 'webhook:claim.status', $body, 'demo-'.$date.'-'.mt_rand(1, 99999))[0]->update(['status' => 'processed', 'processed_at' => now()]);
        $this->summary['inbound']++;
    }

    private function worklist(int $org, IntegrationPartner $scanner): void
    {
        RadiologyExamination::query()->where('organization_id', $org)->orderBy('id')->each(function (RadiologyExamination $exam) {
            if (DicomWorklistEntry::query()->where('examination_id', $exam->id)->exists()) {
                return;
            }
            $entry = $this->dicom->make($exam);
            $entry->update(['status' => match ($exam->status) {
                'completed' => 'completed', 'cancelled' => 'cancelled', 'in_progress' => 'in_progress', default => 'completed'
            }]);
            $this->summary['worklist']++;
        });
    }

    private function nationalShares(HistoryContext $context, IntegrationPartner $national, array $consenting): void
    {
        foreach (array_slice($consenting, 0, 25, true) as $patient) {
            $this->grant($patient, 'national_sharing');
            try {
                $this->national->share($national, $patient, $context->actor);
                $this->summary['shared']++;
            } catch (InteropException) {
            }
        }
    }

    /** After the outage a person sends half of the dead messages again and sets the rest aside with a reason. */
    private function outageClean(HistoryContext $context): void
    {
        $dead = IntegrationMessage::query()->where(['organization_id' => $context->organization->id, 'status' => 'dead'])->orderBy('id')->get();
        foreach ($dead->values() as $n => $m) {
            if ($n % 2 === 0) {
                $this->messages->replay($m, $context->actor);
                $this->summary['replayed']++;
            } else {
                $this->messages->dismiss($m, $context->actor, 'Old; the partner no longer needs it (demo).');
                $this->summary['dismissed']++;
            }
        }
    }
}
