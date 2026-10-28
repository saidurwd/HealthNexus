<?php

namespace App\Services\Portal;

use App\Models\Appointment;
use App\Models\Billing\BillingInvoice;
use App\Models\PatientConsent;
use App\Models\PatientPortalAccount;
use App\Models\Portal\PortalAccessLog;
use App\Models\Portal\PortalResultRelease;
use App\Models\Portal\PortalThread;

/** "Give me my data" (S12): everything the portal holds about the signed-in person, as plain data they can keep. Only their own record, never a relative's; every export is written to the access log. */
class PortalExportService
{
    public function __construct(private readonly PortalRecordsService $records, private readonly PortalAudit $audit, private readonly CommunicationService $comms) {}

    /** @return array<string, mixed> */
    public function build(PatientPortalAccount $account): array
    {
        $patient = $account->patient;
        $this->audit->access($account, $patient->id, 'export');

        return [
            'exported_at' => now()->toIso8601String(),
            'person' => PortalPresenter::patient($patient) + ['phone' => PhoneNumber::mask($account->phone), 'email' => $patient->email, 'language' => $account->language],
            'summary' => (function () use ($patient) {
                $s = $this->records->summary($patient);

                return ['allergies' => $s['allergies']->toArray(), 'problems' => $s['problems']->toArray(), 'visits' => $s['visits']->all()];
            })(),
            'appointments' => Appointment::query()->with('provider:id,name')->where('patient_id', $patient->id)->orderBy('appointment_date')->get()->map(fn ($a) => PortalPresenter::appointment($a))->all(),
            'results' => PortalResultRelease::query()->where('patient_id', $patient->id)->where('status', 'released')->get()->map(fn ($r) => $this->records->view($r))->all(),
            'prescriptions' => $this->records->prescriptions([$patient->id], 200)->map(fn ($p) => PortalPresenter::prescription($p))->all(),
            'invoices' => BillingInvoice::query()->where('patient_id', $patient->id)->whereIn('status', ['finalized', 'partially_paid', 'paid'])->orderBy('invoice_date')->get()->map(fn ($i) => PortalPresenter::invoice($i))->all(),
            'consents' => PatientConsent::query()->where('patient_id', $patient->id)->orderBy('id')->get(['consent_type', 'version', 'status', 'granted_at', 'withdrawn_at'])->toArray(),
            'contact_choices' => $this->comms->matrix($patient),
            'messages' => PortalThread::query()->where('patient_id', $patient->id)->with('messages')->get()->map(fn ($t) => PortalPresenter::thread($t, true))->all(),
            'who_looked_at_my_record' => PortalAccessLog::query()->where('patient_id', $patient->id)->latest('id')->limit(200)->get(['action', 'as_proxy', 'created_at'])->toArray(),
        ];
    }
}
