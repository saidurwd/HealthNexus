<?php

namespace Tests\Feature\Vital;

use App\Models\Branch;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\User;
use App\Services\TenantContextResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/** A hospital with a branch and staff who hold exactly the rights a test names. */
abstract class VitalTestCase extends TestCase
{
    use RefreshDatabase;

    protected Organization $organization;

    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->organization = Organization::factory()->create();
        $this->branch = Branch::factory()->create(['organization_id' => $this->organization->id]);
    }

    /** Signs the person in with the hospital selected. */
    protected function as(User $user): static
    {
        $this->actingAs($user);
        session()->put(['tenant_organization_id' => $this->organization->id, 'tenant_branch_id' => $this->branch->id]);
        app(TenantContextResolver::class)->setOrganizationId($this->organization->id);

        return $this;
    }

    /** @param  list<string>  $rights */
    protected function actor(array $rights = []): User
    {
        $user = User::factory()->create();
        $user->organizations()->attach($this->organization->id, ['access_level' => 'admin']);
        $user->branches()->attach($this->branch->id, ['access_level' => 'manager', 'organization_id' => $this->organization->id]);
        foreach ($rights as $r) {
            Permission::findOrCreate($r, 'web');
            $user->givePermissionTo($r);
        }

        return $user;
    }

    protected function patient(array $o = []): Patient
    {
        return Patient::factory()->create($o + ['organization_id' => $this->organization->id]);
    }

    protected function mother(): Patient
    {
        return $this->patient(['sex' => 'F']);
    }

    /** A body in the mortuary for the patient (the row Facility keeps). */
    protected function mortuaryCase(Patient $patient, array $o = []): int
    {
        return DB::table('fac_mortuary_cases')->insertGetId($o + ['public_id' => (string) Str::ulid(), 'organization_id' => $this->organization->id, 'number' => 'MC-'.Str::random(6), 'patient_id' => $patient->id, 'source_type' => 'ipd_admission', 'source_id' => random_int(1, 99999),
            'died_at' => now()->subHours(8), 'status' => 'stored', 'police_case' => false, 'police_cleared' => false, 'created_at' => now(), 'updated_at' => now()]);
    }

    /** @return array<string,mixed> a valid set of birth details */
    protected function birth(array $o = []): array
    {
        return $o + ['born_at' => now()->subHours(6)->toDateTimeString(), 'sex' => 'female', 'outcome' => 'live_birth', 'place_of_birth' => 'this_hospital', 'weight_g' => 3000, 'gestation_weeks' => 39];
    }

    /** @return array<string,mixed> a valid set of death details */
    protected function death(array $o = []): array
    {
        return $o + ['died_at' => now()->subHours(5)->toDateTimeString(), 'manner' => 'natural', 'cause_a' => 'Septic shock', 'interval_a' => '2 days', 'cause_b' => 'Pneumonia'];
    }
}
