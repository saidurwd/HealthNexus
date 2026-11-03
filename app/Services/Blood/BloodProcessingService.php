<?php

namespace App\Services\Blood;

use App\Models\Blood\BloodUnit;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Separates a whole-blood unit into its components. Every new bag is quarantined like its parent and carries its own number and expiry. */
class BloodProcessingService
{
    public function __construct(private readonly BloodPolicy $policy, private readonly BloodUnitService $units) {}

    /** @return list<BloodUnit> */
    public function separate(BloodUnit $whole, User $by): array
    {
        return DB::transaction(function () use ($whole, $by) {
            $locked = BloodUnit::query()->lockForUpdate()->findOrFail($whole->id);
            if ($locked->component !== 'whole_blood' || $locked->status !== BloodUnit::QUARANTINED) {
                throw ValidationException::withMessages(['unit' => 'Only a quarantined whole-blood unit can be separated.']);
            }
            if (BloodUnit::query()->where('parent_unit_id', $locked->id)->exists()) {
                throw ValidationException::withMessages(['unit' => 'This unit has already been separated.']);
            }

            $made = [];
            $suffix = 1;
            foreach ($this->policy->get('processing') as $component => $count) {
                $definition = $this->policy->component($component);
                for ($i = 0; $i < $count; $i++) {
                    $child = BloodUnit::create([
                        'organization_id' => $locked->organization_id, 'branch_id' => $locked->branch_id, 'donation_id' => $locked->donation_id, 'parent_unit_id' => $locked->id,
                        'unit_number' => preg_replace('/-00$/', '', $locked->unit_number).sprintf('-%02d', $suffix++), 'component' => $component, 'abo' => $locked->abo, 'rh' => $locked->rh,
                        'volume_ml' => $definition['volume'], 'status' => BloodUnit::QUARANTINED, 'location_id' => $locked->location_id,
                        'collected_at' => $locked->collected_at, 'expires_at' => $locked->collected_at->copy()->addDays($definition['shelf_days']),
                    ]);
                    $this->units->event($child, 'processed', $by, "Made from {$locked->unit_number}", $locked, null, BloodUnit::QUARANTINED);
                    $made[] = $child;
                }
            }
            $this->units->move($locked, BloodUnit::PROCESSED, $by, 'Separated into components');

            return $made;
        });
    }
}
