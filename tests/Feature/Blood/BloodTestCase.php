<?php

namespace Tests\Feature\Blood;

use App\Models\Blood\BloodDonation;
use App\Models\Blood\BloodDonor;
use App\Models\Blood\BloodRequest;
use App\Models\Blood\BloodUnit;
use App\Models\Branch;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\User;
use App\Services\Blood\BloodCompatibilityService;
use App\Services\Blood\BloodDonationService;
use App\Services\Blood\BloodDonorService;
use App\Services\Blood\BloodMasterData;
use App\Services\Blood\BloodProcessingService;
use App\Services\Blood\BloodRequestService;
use App\Services\Blood\BloodTestingService;
use App\Services\TenantContextResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

abstract class BloodTestCase extends TestCase
{
    use RefreshDatabase;

    protected Organization $organization;

    protected Branch $branch;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->organization = Organization::factory()->create();
        $this->branch = Branch::factory()->create(['organization_id' => $this->organization->id]);
        $this->user = $this->staff(['super_admin']);
        $this->actingAs($this->user);
        $this->context();
        app(BloodMasterData::class)->ensure($this->organization->id);
    }

    protected function context(?Organization $organization = null, ?Branch $branch = null): void
    {
        $organization ??= $this->organization;
        $branch ??= $this->branch;
        session()->put(['tenant_organization_id' => $organization->id, 'tenant_branch_id' => $branch->id]);
        app(TenantContextResolver::class)->setOrganizationId($organization->id);
        app(TenantContextResolver::class)->setBranchId($branch->id);
    }

    protected function staff(array $roles = [], ?Organization $organization = null, ?Branch $branch = null): User
    {
        $organization ??= $this->organization;
        $branch ??= $this->branch;
        $user = User::factory()->create();
        $user->organizations()->attach($organization->id, ['access_level' => 'admin']);
        $user->branches()->attach($branch->id, ['access_level' => 'manager', 'organization_id' => $organization->id]);
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
            $user->assignRole($role);
        }

        return $user;
    }

    protected function patient(array $o = []): Patient
    {
        return Patient::factory()->create(array_merge(['organization_id' => $this->organization->id, 'middle_name' => null, 'blood_group' => null, 'rh_factor' => null], $o));
    }

    protected function donor(string $sex = 'M'): BloodDonor
    {
        $donor = app(BloodDonorService::class)->register($this->user, $this->branch, [
            'first_name' => 'Donor', 'last_name' => fake()->lastName(), 'sex' => $sex, 'date_of_birth' => now()->subYears(30)->toDateString(),
        ]);

        return app(BloodDonorService::class)->recordConsent($donor, $this->user);
    }

    protected function goodScreening(BloodDonor $donor, array $override = [])
    {
        return app(BloodDonorService::class)->screen($donor, $this->user, $override + ['weight_kg' => 70, 'hemoglobin' => 14.5, 'systolic' => 120, 'diastolic' => 80, 'pulse' => 72, 'temperature_c' => 36.8, 'questionnaire' => []]);
    }

    /** A collected donation whose tests all came back clean with the given group; its units are separated and released onto the shelf. */
    protected function releasedDonation(string $abo = 'O', string $rh = 'positive', ?BloodDonor $donor = null): BloodDonation
    {
        $donor ??= $this->donor();
        $donation = app(BloodDonationService::class)->start($donor, $this->goodScreening($donor), $this->branch, $this->user);
        $donation = app(BloodDonationService::class)->complete($donation, 450, $this->user);
        $this->passTests($donation, $abo, $rh);
        $whole = BloodUnit::where('donation_id', $donation->id)->where('component', 'whole_blood')->firstOrFail();
        app(BloodProcessingService::class)->separate($whole, $this->user);
        app(BloodTestingService::class)->release($donation->refresh(), $this->user);

        return $donation->refresh();
    }

    protected function passTests(BloodDonation $donation, string $abo, string $rh): void
    {
        $testing = app(BloodTestingService::class);
        foreach (array_keys(config('blood.screening_tests')) as $code) {
            $testing->applyResult($donation, $code, 'Negative');
        }
        $testing->applyResult($donation, config('blood.grouping_tests.abo'), $abo);
        $testing->applyResult($donation, config('blood.grouping_tests.rh'), $rh);
    }

    protected function unit(string $component = 'prbc', string $abo = 'O', string $rh = 'positive'): BloodUnit
    {
        return BloodUnit::where('donation_id', $this->releasedDonation($abo, $rh)->id)->where('component', $component)->firstOrFail();
    }

    /** A patient with a confirmed group and an open request with an accepted sample. */
    protected function readyRequest(string $abo = 'A', string $rh = 'positive', string $component = 'prbc', int $units = 1, string $priority = 'routine'): BloodRequest
    {
        $patient = $this->patient();
        $requests = app(BloodRequestService::class);
        $request = $requests->create($this->user, $patient, $component, $units, $priority);
        foreach ([now()->subMinutes(30), now()->subMinutes(10)] as $when) {
            $requests->receiveSample($request, $this->user, ['patient_public_id' => $patient->public_id, 'abo' => $abo, 'rh' => $rh, 'collected_at' => $when]);
        }

        return $request->refresh();
    }

    protected function reserve(BloodRequest $request, BloodUnit $unit)
    {
        return app(BloodCompatibilityService::class)->crossmatchAndReserve($request, $unit, $this->user);
    }
}
