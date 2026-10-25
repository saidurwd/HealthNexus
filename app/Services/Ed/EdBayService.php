<?php

namespace App\Services\Ed;

use App\Events\Ed\EdBayAssigned;
use App\Models\Ed\EdBay;
use App\Models\Ed\EdVisit;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Two staff can never take the same bay: the bay row is locked, checked and claimed in one transaction (unique index behind it). */
class EdBayService
{
    public function assign(EdVisit $visit, EdBay $bay, User $by): EdVisit
    {
        return DB::transaction(function () use ($visit, $bay, $by) {
            $lockedBay = EdBay::query()->lockForUpdate()->with('zone')->findOrFail($bay->id);
            $lockedVisit = EdVisit::query()->lockForUpdate()->findOrFail($visit->id);

            if ($lockedBay->organization_id !== $lockedVisit->organization_id) {
                throw ValidationException::withMessages(['bay' => 'Bay not found.']);
            }
            if (! $lockedVisit->isActive()) {
                throw ValidationException::withMessages(['visit' => 'This visit is closed.']);
            }
            if (! $lockedBay->is_active || $lockedBay->status !== EdBay::AVAILABLE || $lockedBay->current_visit_id !== null) {
                throw ValidationException::withMessages(['bay' => "Bay {$lockedBay->code} is not available."]);
            }

            // Moving a patient frees the bay they leave.
            $lockedVisit->bay_id && $this->release($lockedVisit, $by, EdBay::CLEANING);

            $lockedBay->update(['status' => EdBay::OCCUPIED, 'current_visit_id' => $lockedVisit->id]);
            DB::table('ed_bay_assignments')->insert(['visit_id' => $lockedVisit->id, 'bay_id' => $lockedBay->id, 'assigned_at' => now(), 'assigned_by' => $by->id]);
            $lockedVisit->update(['bay_id' => $lockedBay->id, 'zone_id' => $lockedBay->zone_id]);

            EdBayAssigned::dispatch($lockedVisit->id, $lockedVisit->organization_id, $lockedVisit->patient_id);

            return $lockedVisit->refresh();
        });
    }

    /** @param  string  $newStatus  what the bay becomes: cleaning (default) or available */
    public function release(EdVisit $visit, User $by, string $newStatus = EdBay::CLEANING): EdVisit
    {
        return DB::transaction(function () use ($visit, $by, $newStatus) {
            $locked = EdVisit::query()->lockForUpdate()->findOrFail($visit->id);

            if ($locked->bay_id) {
                EdBay::query()->lockForUpdate()->whereKey($locked->bay_id)->update(['status' => $newStatus, 'current_visit_id' => null]);
                DB::table('ed_bay_assignments')->where('visit_id', $locked->id)->whereNull('released_at')->update(['released_at' => now(), 'released_by' => $by->id]);
                $locked->update(['bay_id' => null]);
            }

            return $locked->refresh();
        });
    }

    public function setStatus(EdBay $bay, string $status): EdBay
    {
        if (! in_array($status, [EdBay::AVAILABLE, EdBay::CLEANING, EdBay::BLOCKED], true)) {
            throw ValidationException::withMessages(['status' => 'Use assign/release to occupy or free a bay.']);
        }

        return DB::transaction(function () use ($bay, $status) {
            $locked = EdBay::query()->lockForUpdate()->findOrFail($bay->id);

            if ($locked->current_visit_id !== null) {
                throw ValidationException::withMessages(['bay' => "Bay {$locked->code} holds a patient; release the patient first."]);
            }

            $locked->update(['status' => $status]);

            return $locked;
        });
    }
}
