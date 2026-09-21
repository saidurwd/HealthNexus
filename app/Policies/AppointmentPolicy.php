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
        return $user->companies()->where('companies.id', $appointment->company_id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->can('appointments.create') || $user->hasRole('super_admin');
    }

    public function update(User $user, Appointment $appointment): bool
    {
        return $user->can('appointments.update') || $user->hasRole('super_admin');
    }

    public function delete(User $user, Appointment $appointment): bool
    {
        return $user->can('appointments.delete') || $user->hasRole('super_admin');
    }
}
