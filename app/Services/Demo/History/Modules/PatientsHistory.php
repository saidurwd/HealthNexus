<?php

namespace App\Services\Demo\History\Modules;

use App\Models\Patient;
use App\Models\User;
use App\Services\Demo\History\DemoHistoryModule;
use App\Services\Demo\History\HistoryContext;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/** Staff to act as (doctors, nurses, technicians …) and enough patients, registered over the whole period, with allergies and problems. */
class PatientsHistory implements DemoHistoryModule
{
    private const STAFF = ['doctor' => 4, 'staff_nurse' => 3, 'lab_technician' => 2, 'radiology_technician' => 1, 'radiologist' => 1, 'pharmacist' => 2, 'cashier' => 2, 'receptionist' => 2, 'surgeon' => 2, 'anesthetist' => 1, 'icu_physician' => 1, 'charge_nurse' => 1];

    public function name(): string
    {
        return 'Patients and staff';
    }

    public function module(): string
    {
        return 'patient';
    }

    public function run(HistoryContext $context): string
    {
        $this->staff($context);

        $target = max(20, $context->visitsPerDay * 12);
        $missing = $target - $context->patients()->count();
        $created = 0;

        for ($i = 0; $i < $missing; $i++) {
            $registered = $context->from->addDays(mt_rand(0, max(1, (int) $context->from->diffInDays($context->to) - 1)));
            $context->at($registered->setTime(9, 0), function () use ($context, &$created) {
                $patient = Patient::factory()->create(['organization_id' => $context->organization->id, 'middle_name' => null, 'registered_at' => now(), 'registered_by' => $context->actor->id]);
                $patient->branchRegistrations()->create(['organization_id' => $context->organization->id, 'branch_id' => $context->branch->id, 'registered_by' => $context->actor->id, 'registered_at' => now()]);
                $created++;
            });
        }

        return "$created patients added, ".$context->patients()->count().' in total';
    }

    private function staff(HistoryContext $context): void
    {
        foreach (self::STAFF as $role => $count) {
            if (! Role::where('name', $role)->exists()) {
                continue;
            }
            for ($n = $context->staff($role)->filter(fn (User $u) => $u->hasRole($role))->count(); $n < $count; $n++) {
                $user = User::create(['name' => ucwords(str_replace('_', ' ', $role)).' '.($n + 1), 'email' => sprintf('demo.%s%d.%s@%s.example.invalid', $role, $n + 1, $context->organization->id, strtolower($context->organization->code)), 'password' => Hash::make(str()->random(24)), 'is_active' => true]);
                $user->organizations()->attach($context->organization->id, ['access_level' => 'staff']);
                $user->branches()->attach($context->branch->id, ['access_level' => 'staff', 'organization_id' => $context->organization->id]);
                $user->assignRole($role);
            }
        }
    }
}
