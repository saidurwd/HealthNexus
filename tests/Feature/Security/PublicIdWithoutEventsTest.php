<?php

namespace Tests\Feature\Security;

use App\Models\Blood\BloodStorageLocation;
use App\Models\Organization;
use Database\Seeders\BloodBankSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicIdWithoutEventsTest extends TestCase
{
    use RefreshDatabase;

    /** DatabaseSeeder uses WithoutModelEvents, which used to leave public_id empty and fail on MySQL. */
    public function test_a_record_created_with_model_events_off_still_gets_its_public_id(): void
    {
        $organization = Organization::factory()->create();

        $location = Model::withoutEvents(fn () => BloodStorageLocation::create([
            'organization_id' => $organization->id, 'code' => 'X1', 'name' => 'Fridge', 'kind' => 'fridge', 'min_temp_c' => 2, 'max_temp_c' => 6,
        ]));

        $this->assertMatchesRegularExpression('/^[0-9A-Z]{26}$/', $location->public_id);
    }

    public function test_the_blood_bank_seeder_runs_under_the_database_seeder_rules(): void
    {
        Organization::factory()->create();

        Model::withoutEvents(fn () => $this->seed(BloodBankSeeder::class));

        $this->assertGreaterThan(0, BloodStorageLocation::count());
        $this->assertSame(0, BloodStorageLocation::whereNull('public_id')->orWhere('public_id', '')->count());
    }
}
