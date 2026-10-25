<?php

namespace Tests\Feature\Security;

use App\Models\Appointment;
use App\Models\Encounter;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PublicIdTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_records_get_a_ulid_public_id(): void
    {
        $organization = Organization::factory()->create();

        foreach ([Patient::factory(), Encounter::factory(), Appointment::factory()] as $factory) {
            $model = $factory->create(['organization_id' => $organization->id]);

            $this->assertMatchesRegularExpression('/^[0-9A-HJKMNP-TV-Z]{26}$/', $model->public_id);
            $this->assertSame($model->public_id, $model->getRouteKey());
        }
    }

    public function test_api_resolves_a_patient_by_public_id_and_refuses_the_numeric_id(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $user->organizations()->attach($organization->id, ['access_level' => 'admin']);
        Permission::create(['name' => 'patients.view', 'guard_name' => 'web']);
        $user->givePermissionTo('patients.view');
        $patient = Patient::factory()->create(['organization_id' => $organization->id]);

        $this->actingAs($user, 'sanctum')->withHeader('X-Organization-Id', (string) $organization->id);

        $this->getJson("/api/v1/patients/{$patient->public_id}")->assertOk();
        $this->getJson("/api/v1/patients/{$patient->id}")->assertNotFound();
    }
}
