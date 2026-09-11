<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Encounter;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class EncounterService
{
    public function createEncounter(array $data, User $user): Encounter
    {
        return DB::transaction(function () use ($data, $user): Encounter {
            $company = $user->companies()->findOrFail($data['company_id']);

            $data['company_id'] = $company->id;
            $data['encounter_no'] = $this->generateEncounterNo($company);

            return Encounter::create($data);
        });
    }

    public function updateEncounter(Encounter $encounter, array $data): Encounter
    {
        $encounter->update($data);

        return $encounter;
    }

    public function closeEncounter(Encounter $encounter): Encounter
    {
        $encounter->update([
            'status' => 'completed',
            'ended_at' => now(),
        ]);

        return $encounter;
    }

    private function generateEncounterNo(Company $company): string
    {
        $prefix = strtoupper(substr($company->code, 0, 3));
        $lastEncounter = Encounter::where('company_id', $company->id)
            ->orderByDesc('id')
            ->first();

        $sequence = $lastEncounter ? $lastEncounter->id + 1 : 1;

        return sprintf('%s-ENC-%08d', $prefix, $sequence);
    }
}
