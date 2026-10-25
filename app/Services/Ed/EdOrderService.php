<?php

namespace App\Services\Ed;

use App\Models\ClinicalOrder;
use App\Models\Ed\EdMedicationAdministration;
use App\Models\Ed\EdVisit;
use App\Models\EncounterProcedure;
use App\Models\Prescription;
use App\Models\User;
use App\Services\Clinical\EncounterClinicalService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Orders from the department. Tests and scans go through the clinical order of the ED encounter, so Laboratory and Radiology receive them by
 * their existing listeners; prescriptions reach Pharmacy the same way. Nothing here talks to those modules directly.
 */
class EdOrderService
{
    public const ORDER_TYPES = ['laboratory', 'radiology'];

    public function __construct(private readonly EncounterClinicalService $clinical) {}

    /** @param  list<array<string, mixed>>  $items */
    public function placeOrder(EdVisit $visit, User $by, string $type, array $items, ?string $priority = null, ?string $notes = null): ClinicalOrder
    {
        $this->assertActive($visit);
        if (! in_array($type, self::ORDER_TYPES, true)) {
            throw ValidationException::withMessages(['order_type' => 'Unknown order type.']);
        }
        $items = array_values(array_filter($items, fn ($i) => filled($i['name'] ?? null)));
        if ($items === []) {
            throw ValidationException::withMessages(['items' => 'Add at least one item.']);
        }

        return $this->clinical->createOrder($visit->encounter, [
            'order_type' => $type, 'priority' => $priority ?? $this->defaultPriority($visit), 'notes' => $notes, 'provider_id' => $by->id,
            'items' => array_map(fn ($i) => ['item_name' => $i['name'], 'item_code' => $i['code'] ?? null, 'quantity' => 1, 'status' => 'requested'], $items),
        ], $by);
    }

    /** @param  list<array<string, mixed>>  $items */
    public function prescribe(EdVisit $visit, User $by, array $items): Prescription
    {
        $this->assertActive($visit);
        $items = array_values(array_filter($items, fn ($i) => filled($i['medicine_name'] ?? null)));
        if ($items === []) {
            throw ValidationException::withMessages(['items' => 'Add at least one medicine.']);
        }

        foreach ($items as $item) {
            if (blank($item['frequency'] ?? null) || blank($item['duration'] ?? null)) {
                throw ValidationException::withMessages(['items' => 'Every medicine needs how often and for how long.']);
            }
        }

        return DB::transaction(function () use ($visit, $by, $items) {
            $prescription = $this->clinical->createPrescription($visit->encounter, ['doctor_id' => $by->id, 'items' => $items], $by);

            return $this->clinical->issuePrescription($visit->encounter, $prescription, $by);
        });
    }

    /** A dose given. A verbal order must name the doctor who gave it, so it can be countersigned later. */
    public function giveMedication(EdVisit $visit, User $by, array $data): EdMedicationAdministration
    {
        $this->assertActive($visit);
        $verbal = (bool) ($data['is_verbal_order'] ?? false);
        if ($verbal && blank($data['ordered_by'] ?? null)) {
            throw ValidationException::withMessages(['ordered_by' => 'Name the doctor who gave the verbal order.']);
        }

        return EdMedicationAdministration::create([
            'organization_id' => $visit->organization_id, 'visit_id' => $visit->id, 'drug_name' => $data['drug_name'], 'dose' => $data['dose'], 'route' => $data['route'],
            'is_verbal_order' => $verbal, 'ordered_by' => $data['ordered_by'] ?? null, 'notes' => $data['notes'] ?? null, 'given_at' => $data['given_at'] ?? now(), 'given_by' => $by->id,
        ]);
    }

    public function recordProcedure(EdVisit $visit, User $by, string $name, ?string $notes = null): EncounterProcedure
    {
        $this->assertActive($visit);

        return $this->clinical->addProcedure($visit->encounter, ['procedure_name' => $name, 'notes' => $notes, 'provider_id' => $by->id], $by);
    }

    /** Levels 1–2 are sent as stat; the rest as routine. */
    private function defaultPriority(EdVisit $visit): string
    {
        return $visit->triage_level !== null && $visit->triage_level <= 2 ? 'stat' : 'routine';
    }

    private function assertActive(EdVisit $visit): void
    {
        if (! $visit->isActive()) {
            throw ValidationException::withMessages(['visit' => 'This visit is closed.']);
        }
    }
}
