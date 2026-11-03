<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Services\Blood\BloodMasterData;
use Illuminate\Database\Seeder;

/** Storage locations, blood-bank laboratory tests and price-list items for every organization (idempotent). */
class BloodBankSeeder extends Seeder
{
    public function run(): void
    {
        Organization::all()->each(fn (Organization $organization) => app(BloodMasterData::class)->ensure($organization->id));
    }
}
