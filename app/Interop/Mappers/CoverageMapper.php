<?php

namespace App\Interop\Mappers;

use App\Models\Billing\BillingInsurancePolicy;
use App\Models\Insurance\InsurancePayer;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Model;

class CoverageMapper extends Mapper
{
    public function type(): string
    {
        return 'Coverage';
    }

    public function sources(): array
    {
        return [['model' => BillingInsurancePolicy::class, 'entity' => 'BillingInsurancePolicy', 'patient' => 'patient_id', 'search' => ['status' => fn ($q, $v) => $q->where('status', $v)]]];
    }

    /** @param BillingInsurancePolicy $m */
    public function toFhir(Model $m): array
    {
        return array_filter($this->base($m) + [
            'identifier' => [['system' => 'urn:healthnexus:policy', 'value' => $m->policy_number]],
            'status' => $m->status === 'active' ? 'active' : 'cancelled',
            'subscriberId' => $m->member_number,
            'beneficiary' => $this->ref('Patient', Patient::class, $m->patient_id),
            'relationship' => ['coding' => [$this->coding('http://terminology.hl7.org/CodeSystem/subscriber-relationship', $m->subscriber_patient_id && $m->subscriber_patient_id !== $m->patient_id ? 'other' : 'self')]],
            'period' => array_filter(['start' => $this->date($m->effective_from), 'end' => $this->date($m->effective_to)]),
            'payor' => $m->payer_id ? array_filter([$this->ref('Organization', InsurancePayer::class, $m->payer_id, null, 'InsurancePayer')]) : null,
            'order' => $m->coverage_order,
            'class' => $m->group_number ? [['type' => ['coding' => [$this->coding('http://terminology.hl7.org/CodeSystem/coverage-class', 'group')]], 'value' => $m->group_number]] : null,
        ], fn ($v) => $v !== null && $v !== []);
    }
}
