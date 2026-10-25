<?php

namespace Tests\Feature\Ux;

use App\Models\Branch;
use App\Models\Encounter;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\PatientAlert;
use App\Models\PatientAllergy;
use App\Models\User;
use App\Services\ModuleManager;
use App\Services\Ux\PatientBannerData;
use App\Services\Ux\RoleHome;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/** H-14: patient banner, one status palette, role home screens, keyboard help and error references. */
class UxStandardTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->organization = Organization::factory()->create();
        $this->branch = Branch::factory()->create(['organization_id' => $this->organization->id]);
    }

    private function user(array $roles = [], array $permissions = []): User
    {
        $user = User::factory()->create();
        $user->organizations()->attach($this->organization->id, ['access_level' => 'admin']);
        $user->branches()->attach($this->branch->id, ['access_level' => 'manager', 'organization_id' => $this->organization->id]);
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
            $user->assignRole($role);
        }
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
            $user->givePermissionTo($permission);
        }

        return $user;
    }

    private function patient(): Patient
    {
        return Patient::factory()->create(['organization_id' => $this->organization->id, 'first_name' => 'Rahim', 'middle_name' => null, 'last_name' => 'Uddin', 'sex' => 'M', 'blood_group' => 'O+', 'date_of_birth' => now()->subYears(42)->subDays(10)]);
    }

    public function test_banner_shows_identity_every_allergy_and_every_alert_most_severe_first(): void
    {
        $patient = $this->patient();
        foreach ([['Dust', 'mild'], ['Penicillin', 'severe'], ['Aspirin', 'moderate']] as [$substance, $severity]) {
            PatientAllergy::create(['organization_id' => $this->organization->id, 'patient_id' => $patient->id, 'substance' => $substance, 'severity' => $severity, 'is_active' => true]);
        }
        PatientAllergy::create(['organization_id' => $this->organization->id, 'patient_id' => $patient->id, 'substance' => 'Retired', 'severity' => 'mild', 'is_active' => false]);
        PatientAlert::create(['organization_id' => $this->organization->id, 'patient_id' => $patient->id, 'alert_type' => 'clinical', 'title' => 'Difficult airway', 'severity' => 'critical', 'status' => 'active']);

        $html = Blade::render('<x-patient-banner :patient="$patient" />', ['patient' => $patient]);

        $this->assertStringContainsString('Rahim Uddin', $html);
        $this->assertStringContainsString('42 years', $html);
        $this->assertStringContainsString('O+', $html);
        $this->assertStringContainsString($patient->enterprise_patient_no, $html);
        $this->assertStringContainsString('Difficult airway', $html);
        $this->assertStringNotContainsString('Retired', $html, 'inactive allergies are not shown');
        $this->assertLessThan(strpos($html, 'Aspirin'), strpos($html, 'Penicillin'), 'severe first');
        $this->assertLessThan(strpos($html, 'Dust'), strpos($html, 'Aspirin'));
    }

    public function test_banner_says_so_when_there_are_no_allergies_and_shows_the_bed_of_an_admitted_patient(): void
    {
        $patient = $this->patient();

        $this->assertStringContainsString('No known allergies recorded', Blade::render('<x-patient-banner :patient="$patient" />', ['patient' => $patient]));
        $this->assertNull(app(PatientBannerData::class)->for($patient)['location']);
    }

    public function test_banner_is_on_the_clinical_screens(): void
    {
        $patient = $this->patient();
        PatientAllergy::create(['organization_id' => $this->organization->id, 'patient_id' => $patient->id, 'substance' => 'Sulfa', 'severity' => 'severe', 'is_active' => true]);
        $user = $this->user([], ['manage organizations', 'encounters.view']);
        $this->actingAs($user);
        app(\App\Services\TenantContextResolver::class)->setOrganizationId($this->organization->id);
        session()->put('tenant_organization_id', $this->organization->id);
        $encounter = Encounter::factory()->create(['organization_id' => $this->organization->id, 'branch_id' => $this->branch->id, 'patient_id' => $patient->id]);

        $this->get('/admin/encounters/'.$encounter->public_id)->assertOk()->assertSee('Sulfa')->assertSee('patient-banner', false);
    }

    public function test_status_badge_is_colour_icon_and_text_from_one_palette(): void
    {
        $html = Blade::render('<x-ui.status-badge status="no_show" />');

        $this->assertStringContainsString('text-bg-danger', $html);
        $this->assertStringContainsString('bi-person-x', $html);
        $this->assertStringContainsString('No Show', $html);

        $unknown = Blade::render('<x-ui.status-badge status="brand_new_state" />');
        $this->assertStringContainsString('text-bg-secondary', $unknown);
        $this->assertStringContainsString('Brand New State', $unknown);
    }

    public function test_every_palette_entry_is_complete(): void
    {
        foreach (config('ux.statuses') as $status => $entry) {
            $this->assertCount(2, $entry, $status);
            $this->assertContains($entry[0], ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'dark'], $status);
            $this->assertStringStartsWith('bi-', $entry[1], $status);
        }
    }

    public function test_each_role_lands_on_its_own_worklist_when_allowed_otherwise_on_the_dashboard(): void
    {
        $home = app(RoleHome::class);

        $this->assertSame(route('admin.queue.index'), $home->urlFor($this->user(['doctor'], ['queue.view'])));
        $this->assertSame(route('admin.billing.cashier.index'), $home->urlFor($this->user(['cashier'], ['billing.dashboard.view'])));
        $this->assertSame(route('admin.pharmacy.orders.index'), $home->urlFor($this->user(['pharmacist'], ['pharmacy.dashboard.view'])));
        $this->assertSame(route('admin.lab.worklist.index'), $home->urlFor($this->user(['lab_technician'], ['lab.dashboard.view'])));
        $this->assertSame(route('home'), $home->urlFor($this->user(['doctor'], [])), 'no permission, so the dashboard');
        $this->assertSame(route('home'), $home->urlFor($this->user(['hospital_admin'], [])), 'managers keep the dashboard');
        $this->assertSame(route('home'), $home->urlFor($this->user([], [])));
    }

    public function test_role_home_skips_a_module_that_is_switched_off(): void
    {
        $user = $this->user(['pharmacist'], ['pharmacy.dashboard.view']);
        app(ModuleManager::class)->set('pharmacy', false, $this->organization->id);
        app(\App\Services\TenantContextResolver::class)->setOrganizationId($this->organization->id);

        $this->assertSame(route('home'), app(RoleHome::class)->urlFor($user));
    }

    public function test_every_configured_role_home_points_at_a_real_route(): void
    {
        foreach (config('ux.role_homes') as $role => $home) {
            $this->assertTrue(\Illuminate\Support\Facades\Route::has($home['route']), "$role → {$home['route']}");
        }
    }

    public function test_screens_offer_keyboard_help_and_fast_entry(): void
    {
        $this->actingAs($this->user([], ['manage organizations', 'patients.view', 'patients.create']));
        app(\App\Services\TenantContextResolver::class)->setOrganizationId($this->organization->id);
        session()->put('tenant_organization_id', $this->organization->id);

        $this->get('/admin/patients/create')->assertOk()->assertSee('shortcut-help', false)->assertSee('data-enter-next', false)->assertSee('Keyboard shortcuts');
    }

    public function test_error_pages_show_a_reference_to_quote_to_support(): void
    {
        $this->actingAs($this->user());
        app()->instance('request_id', '20261025-101500-abcd1234');

        $html = view('adminlte::errors.500')->render();

        $this->assertStringContainsString('20261025-101500-abcd1234', $html);
        $this->assertStringContainsString('Tell support this reference', $html);
    }

    public function test_empty_state_component_says_what_to_do_next(): void
    {
        $html = Blade::render('<x-ui.empty-state title="No beds free" hint="Check the discharge list." />');

        $this->assertStringContainsString('No beds free', $html);
        $this->assertStringContainsString('Check the discharge list.', $html);
    }
}
