<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AppointmentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Appointment $appointment): bool
    {
        return $this->inScope($user, $appointment);
    }

    public function create(User $user): bool
    {
        return $user->can('appointments.create') || $user->hasRole('super_admin');
    }

    /**
     * update()/delete() previously checked only the global permission grant, never the
     * appointment's company — a user with appointments.update in Company A could confirm/
     * reschedule/cancel/delete Company B's appointments by guessing an ID. Every mutating
     * ability below now re-verifies tenant scope, matching the fix already applied to
     * PatientPolicy.
     */
    public function update(User $user, Appointment $appointment): bool
    {
        return ($user->can('appointments.update') && $this->inScope($user, $appointment)) || $user->hasRole('super_admin');
    }

    public function delete(User $user, Appointment $appointment): bool
    {
        return ($user->can('appointments.delete') && $this->inScope($user, $appointment)) || $user->hasRole('super_admin');
    }

    public function confirm(User $user, Appointment $appointment): bool
    {
        return ($user->can('appointments.confirm') && $this->inScope($user, $appointment)) || $user->hasRole('super_admin');
    }

    public function checkIn(User $user, Appointment $appointment): bool
    {
        return ($user->can('appointments.checkin') && $this->inScope($user, $appointment)) || $user->hasRole('super_admin');
    }

    public function cancel(User $user, Appointment $appointment): bool
    {
        return ($user->can('appointments.cancel') && $this->inScope($user, $appointment)) || $user->hasRole('super_admin');
    }

    public function reschedule(User $user, Appointment $appointment): bool
    {
        return ($user->can('appointments.reschedule') && $this->inScope($user, $appointment)) || $user->hasRole('super_admin');
    }

    public function markNoShow(User $user, Appointment $appointment): bool
    {
        return ($user->can('appointments.no_show') && $this->inScope($user, $appointment)) || $user->hasRole('super_admin');
    }

    public function print(User $user, Appointment $appointment): bool
    {
        return ($user->can('appointments.print') && $this->inScope($user, $appointment)) || $user->hasRole('super_admin');
    }

    public function override(User $user): bool
    {
        return $user->can('appointments.override') || $user->hasRole('super_admin');
    }

    public function manageQueue(User $user): bool
    {
        return $user->can('appointments.queue') || $user->hasRole('super_admin');
    }

    public function manageToken(User $user): bool
    {
        return $user->can('appointments.token') || $user->hasRole('super_admin');
    }

    public function manageSchedule(User $user): bool
    {
        return $user->can('appointments.manage_schedule') || $user->hasRole('super_admin');
    }

    public function manageProvider(User $user): bool
    {
        return $user->can('appointments.manage_provider') || $user->hasRole('super_admin');
    }

    public function manageHoliday(User $user): bool
    {
        return $user->can('appointments.manage_holiday') || $user->hasRole('super_admin');
    }

    public function manageBlock(User $user): bool
    {
        return $user->can('appointments.manage_block') || $user->hasRole('super_admin');
    }

    private function inScope(User $user, Appointment $appointment): bool
    {
        return $user->companies()->where('companies.id', $appointment->company_id)->exists();
    }
}
