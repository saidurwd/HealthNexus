<?php

namespace App\Services\Search;

use App\Models\Billing\BillingInvoice;
use App\Models\Encounter;
use App\Models\Ipd\IpdAdmission;
use App\Models\Laboratory\LabSpecimen;
use App\Models\Patient;
use App\Models\Pharmacy\PharmacyBatch;
use App\Models\Radiology\RadiologyOrder;
use App\Models\User;
use App\Services\TenantContextResolver;

/**
 * Turns a scanned code (wristband, specimen label, drug pack, document number) into the record it names (guideline 14.5).
 * Exact matches only, inside the active organization, and only for modules the user may open.
 * Drug-pack labels printed on receiving use the text PHB|<medication code>|<batch number>.
 */
class ScanResolver
{
    public function __construct(private readonly TenantContextResolver $tenant) {}

    /** @return array{type: string, label: string, reference: string, url: ?string}|null */
    public function resolve(User $user, string $code): ?array
    {
        $code = trim($code);
        $organizationId = $this->tenant->getOrganizationId();

        if ($code === '' || $organizationId === null) {
            return null;
        }

        foreach ([
            'patients.view' => fn () => $this->patient($organizationId, $code),
            'lab.specimen.view' => fn () => $this->specimen($organizationId, $code),
            'radiology.order.view' => fn () => $this->radiologyOrder($organizationId, $code),
            'pharmacy.stock.view' => fn () => $this->pharmacyPack($organizationId, $code),
            'billing.invoice.view' => fn () => $this->invoice($organizationId, $code),
            'ipd.admission.view' => fn () => $this->admission($organizationId, $code),
            'encounters.view' => fn () => $this->encounter($organizationId, $code),
        ] as $permission => $matcher) {
            if ($user->can($permission) && ($hit = $matcher())) {
                return $hit;
            }
        }

        return null;
    }

    private function patient(int $org, string $code): ?array
    {
        $patient = Patient::query()->where('organization_id', $org)
            ->where(fn ($q) => $q->where('public_id', $code)->orWhere('enterprise_patient_no', $code))->first();

        return $patient ? ['type' => 'patient', 'label' => $patient->full_name, 'reference' => $patient->enterprise_patient_no, 'url' => route('admin.patients.show', $patient)] : null;
    }

    private function specimen(int $org, string $code): ?array
    {
        $specimen = LabSpecimen::query()->where('organization_id', $org)->where(fn ($q) => $q->where('accession_number', $code)->orWhere('barcode', $code))->first();

        return $specimen ? ['type' => 'lab_specimen', 'label' => 'Specimen '.$specimen->accession_number, 'reference' => $specimen->accession_number, 'url' => route('admin.lab.specimens.show', $specimen)] : null;
    }

    private function radiologyOrder(int $org, string $code): ?array
    {
        $order = RadiologyOrder::query()->where('organization_id', $org)->where('accession_number', $code)->first();

        return $order ? ['type' => 'radiology_order', 'label' => 'Radiology '.$order->accession_number, 'reference' => $order->accession_number, 'url' => route('admin.radiology.orders.show', $order)] : null;
    }

    private function pharmacyPack(int $org, string $code): ?array
    {
        if (! str_starts_with($code, 'PHB|') || count($parts = explode('|', $code)) !== 3) {
            return null;
        }

        $batch = PharmacyBatch::query()->where('organization_id', $org)->where('batch_number', $parts[2])
            ->whereHas('medication', fn ($q) => $q->where('code', $parts[1]))->with('medication')->first();

        return $batch ? ['type' => 'pharmacy_batch', 'label' => $batch->medication->name.' · batch '.$batch->batch_number, 'reference' => $code, 'url' => null] : null;
    }

    private function invoice(int $org, string $code): ?array
    {
        $invoice = BillingInvoice::query()->where('organization_id', $org)->where('invoice_number', $code)->first();

        return $invoice ? ['type' => 'invoice', 'label' => 'Invoice '.$invoice->invoice_number, 'reference' => $invoice->invoice_number, 'url' => route('admin.billing.invoices.show', $invoice)] : null;
    }

    private function admission(int $org, string $code): ?array
    {
        $admission = IpdAdmission::query()->where('organization_id', $org)->where('admission_number', $code)->first();

        return $admission ? ['type' => 'admission', 'label' => 'Admission '.$admission->admission_number, 'reference' => $admission->admission_number, 'url' => route('admin.ipd.admissions.show', $admission)] : null;
    }

    private function encounter(int $org, string $code): ?array
    {
        $encounter = Encounter::query()->where('organization_id', $org)->where('encounter_no', $code)->first();

        return $encounter ? ['type' => 'encounter', 'label' => 'Encounter '.$encounter->encounter_no, 'reference' => $encounter->encounter_no, 'url' => route('admin.encounters.show', $encounter)] : null;
    }
}
