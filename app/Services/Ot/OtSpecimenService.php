<?php

namespace App\Services\Ot;

use App\Events\Ot\SpecimenCollected;
use App\Models\ClinicalOrder;
use App\Models\Ot\OtSpecimen;
use App\Models\Ot\OtSurgery;
use App\Models\User;
use App\Services\Clinical\ClinicalNumberGenerator;

/**
 * Specimen handoff to Lab reuses the existing ClinicalOrder fan-out mechanism (decision #10) —
 * no bespoke Lab integration. Creating a 'laboratory' clinical order here is enough; Lab's own
 * LabRegistrationService::registerFromClinicalOrder() does the rest via the already-wired
 * ClinicalOrderCreated listener.
 */
class OtSpecimenService
{
    public function __construct(
        private readonly OtNumberGenerator $numbers,
        private readonly ClinicalNumberGenerator $clinicalNumbers,
    ) {}

    public function collect(OtSurgery $surgery, array $data, User $user, bool $sendToLab = false): OtSpecimen
    {
        $clinicalOrderId = null;

        if ($sendToLab) {
            $order = ClinicalOrder::create([
                'organization_id' => $surgery->organization_id,
                'branch_id' => $surgery->branch_id,
                'order_number' => $this->clinicalNumbers->generateOrderNumber($surgery->organization, $surgery->branch_id),
                'patient_id' => $surgery->patient_id,
                'encounter_id' => $surgery->encounter_id,
                'provider_id' => $user->id,
                'order_type' => 'laboratory',
                'priority' => 'routine',
                'status' => 'requested',
                'ordered_at' => now(),
                'ordered_by' => $user->id,
                'notes' => "Specimen from surgery {$surgery->surgery_number}: {$data['specimen_type']}",
            ]);

            $clinicalOrderId = $order->id;
        }

        $specimen = OtSpecimen::create([
            ...$data,
            'organization_id' => $surgery->organization_id,
            'branch_id' => $surgery->branch_id,
            'surgery_id' => $surgery->id,
            'patient_id' => $surgery->patient_id,
            'encounter_id' => $surgery->encounter_id,
            'specimen_number' => $this->numbers->generateSpecimenNumber($surgery->organization_id, $surgery->branch_id),
            'collected_at' => $data['collected_at'] ?? now(),
            'collected_by' => $user->id,
            'clinical_order_id' => $clinicalOrderId,
        ]);

        event(new SpecimenCollected($specimen));

        return $specimen;
    }
}
