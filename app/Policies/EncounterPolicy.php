<?php

namespace App\Policies;

use App\Models\BreakGlassAccess;
use App\Models\Encounter;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EncounterPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Encounter $encounter): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        // Break-glass grants view access too — previously only update()/delete() consulted it,
        // so the "emergency access to a restricted record" use case the feature exists for
        // didn't actually work; only its (much more dangerous) write side did.
        if ($this->userHasActiveBreakGlass($user, $encounter)) {
            return true;
        }

        return $this->inScope($user, $encounter);
    }

    public function create(User $user): bool
    {
        return $user->can('encounters.create') || $user->hasRole('super_admin');
    }

    public function update(User $user, Encounter $encounter): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        if ($this->userHasActiveBreakGlass($user, $encounter)) {
            return true;
        }

        if ($encounter->locked_at) {
            return false;
        }

        return $user->can('encounters.update') && $this->inScope($user, $encounter);
    }

    public function delete(User $user, Encounter $encounter): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        if ($this->userHasActiveBreakGlass($user, $encounter)) {
            return true;
        }

        return $user->can('encounters.delete') && $this->inScope($user, $encounter) && ! $encounter->locked_at;
    }

    public function startStop(User $user, Encounter $encounter): bool
    {
        return $this->clinicalAbility($user, $encounter, 'encounter.start');
    }

    public function complete(User $user, Encounter $encounter): bool
    {
        return $this->clinicalAbility($user, $encounter, 'encounter.complete');
    }

    public function cancel(User $user, Encounter $encounter): bool
    {
        return $this->clinicalAbility($user, $encounter, 'encounter.cancel');
    }

    public function lock(User $user, Encounter $encounter): bool
    {
        return $this->clinicalAbility($user, $encounter, 'encounter.lock');
    }

    public function manageVitals(User $user, Encounter $encounter): bool
    {
        return $this->clinicalAbility($user, $encounter, 'clinical.vitals.create');
    }

    public function manageDiagnosis(User $user, Encounter $encounter): bool
    {
        return $this->clinicalAbility($user, $encounter, 'clinical.diagnosis.create');
    }

    public function manageOrder(User $user, Encounter $encounter): bool
    {
        return $this->clinicalAbility($user, $encounter, 'clinical.order.create');
    }

    public function manageReferral(User $user, Encounter $encounter): bool
    {
        return $this->clinicalAbility($user, $encounter, 'clinical.referral.create');
    }

    public function manageNote(User $user, Encounter $encounter): bool
    {
        return $this->clinicalAbility($user, $encounter, 'clinical.note.create');
    }

    /**
     * Covers complaint/history/examination/review-of-systems/problem/procedure/instruction/
     * document — these all share the same intent (structured clinical documentation) and none of
     * them has its own dedicated permission in the spec's list, so they fall under the general
     * clinical documentation ability that clinical.note.create already represents.
     */
    public function manageDocumentation(User $user, Encounter $encounter): bool
    {
        return $this->clinicalAbility($user, $encounter, 'clinical.note.create');
    }

    public function createPrescription(User $user, Encounter $encounter): bool
    {
        return $this->clinicalAbility($user, $encounter, 'prescription.create');
    }

    public function issuePrescription(User $user, Encounter $encounter): bool
    {
        return $this->clinicalAbility($user, $encounter, 'prescription.issue');
    }

    public function cancelPrescription(User $user, Encounter $encounter): bool
    {
        return $this->clinicalAbility($user, $encounter, 'prescription.cancel');
    }

    public function requestAmendment(User $user, Encounter $encounter): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        return $user->can('encounter.amend') && $this->inScope($user, $encounter);
    }

    /**
     * Approving an amendment is intentionally NOT covered by clinicalAbility()'s locked-encounter
     * check — the whole point of the amendment workflow is acting on a locked record.
     */
    public function approveAmendment(User $user, Encounter $encounter): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        return $user->can('encounter.amend') && $this->inScope($user, $encounter);
    }

    /**
     * Shared shape for every clinical sub-record action: correct permission, tenant scope, and
     * (unless break-glass) the encounter must not be locked — a completed/locked encounter
     * previously stayed fully writable for vitals/diagnoses/etc. via the generic 'update' ability,
     * which every action used regardless of which specific permission the actor actually held.
     */
    private function clinicalAbility(User $user, Encounter $encounter, string $permission): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        if ($this->userHasActiveBreakGlass($user, $encounter)) {
            return true;
        }

        if ($encounter->locked_at) {
            return false;
        }

        return $user->can($permission) && $this->inScope($user, $encounter);
    }

    private function inScope(User $user, Encounter $encounter): bool
    {
        if (! $user->companies()->where('companies.id', $encounter->company_id)->exists()) {
            return false;
        }

        if ($encounter->branch_id && $user->branches()->exists()) {
            return $user->branches()->where('branches.id', $encounter->branch_id)->exists();
        }

        return true;
    }

    private function userHasActiveBreakGlass(User $user, Encounter $encounter): bool
    {
        return BreakGlassAccess::where('user_id', $user->id)
            ->where('encounter_id', $encounter->id)
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->exists();
    }
}
