<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\PatientPortalAccount;
use App\Services\Portal\PhoneNumber;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PatientPortalAccount> */
class PatientPortalAccountFactory extends Factory
{
    protected $model = PatientPortalAccount::class;

    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'organization_id' => fn (array $a) => Patient::query()->find($a['patient_id'])->organization_id,
            'phone' => fn (array $a) => PhoneNumber::normalize(Patient::query()->find($a['patient_id'])->phone),
            'password' => 'Portal-Pass-123',
            'status' => 'active',
            'is_active' => true,
            'language' => 'en',
            'activated_at' => now(),
            'terms_accepted_at' => now(),
            'terms_version' => '1',
        ];
    }

    public function invited(): static
    {
        return $this->state(['status' => 'invited', 'is_active' => false, 'password' => null, 'activated_at' => null]);
    }
}
