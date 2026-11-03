<?php

namespace App\Services\Blood;

use App\Models\Blood\BloodUnit;
use App\Models\Blood\BloodUnitEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** The only way a unit changes status. Every change writes an append-only event, so the whole life of a bag can be replayed. */
class BloodUnitService
{
    /** from => allowed next statuses */
    private const MOVES = [
        BloodUnit::QUARANTINED => [BloodUnit::AVAILABLE, BloodUnit::BLOCKED, BloodUnit::DISCARDED, BloodUnit::EXPIRED, BloodUnit::PROCESSED],
        BloodUnit::AVAILABLE => [BloodUnit::RESERVED, BloodUnit::ISSUED, BloodUnit::BLOCKED, BloodUnit::DISCARDED, BloodUnit::EXPIRED],
        BloodUnit::RESERVED => [BloodUnit::AVAILABLE, BloodUnit::ISSUED, BloodUnit::BLOCKED, BloodUnit::DISCARDED, BloodUnit::EXPIRED],
        BloodUnit::ISSUED => [BloodUnit::TRANSFUSED, BloodUnit::AVAILABLE, BloodUnit::DISCARDED, BloodUnit::BLOCKED],
        BloodUnit::BLOCKED => [BloodUnit::AVAILABLE, BloodUnit::QUARANTINED, BloodUnit::DISCARDED, BloodUnit::EXPIRED],
        BloodUnit::TRANSFUSED => [],
        BloodUnit::DISCARDED => [],
        BloodUnit::EXPIRED => [BloodUnit::DISCARDED],
        BloodUnit::PROCESSED => [],
    ];

    public function event(BloodUnit $unit, string $type, ?User $by = null, ?string $note = null, ?object $ref = null, ?string $from = null, ?string $to = null): BloodUnitEvent
    {
        return BloodUnitEvent::create([
            'organization_id' => $unit->organization_id, 'unit_id' => $unit->id, 'event_type' => $type, 'from_status' => $from, 'to_status' => $to,
            'ref_type' => $ref ? $ref::class : null, 'ref_id' => $ref?->getKey(), 'note' => $note, 'actor_id' => $by?->id, 'occurred_at' => now(),
        ]);
    }

    /** Moves a unit to a new status under a row lock; refuses moves the lifecycle does not allow and any move of an expired unit towards use. */
    public function move(BloodUnit $unit, string $to, ?User $by = null, ?string $note = null, ?object $ref = null, array $changes = []): BloodUnit
    {
        return DB::transaction(function () use ($unit, $to, $by, $note, $ref, $changes) {
            $locked = BloodUnit::query()->lockForUpdate()->findOrFail($unit->id);
            $from = $locked->status;

            if ($from === $to) {
                return $locked;
            }
            if (! in_array($to, self::MOVES[$from] ?? [], true)) {
                throw ValidationException::withMessages(['unit' => "A unit that is {$from} cannot become {$to}."]);
            }
            // A unit past its expiry date can never be reserved or issued, whatever the role (blood-specific acceptance).
            if (in_array($to, [BloodUnit::AVAILABLE, BloodUnit::RESERVED, BloodUnit::ISSUED], true) && $locked->isExpired()) {
                throw ValidationException::withMessages(['unit' => "Unit {$locked->unit_number} has expired and cannot be used."]);
            }

            $locked->update(['status' => $to] + $changes);
            $this->event($locked, 'status_changed', $by, $note, $ref, $from, $to);

            return $locked->refresh();
        });
    }
}
