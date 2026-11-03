<?php

namespace Tests\Feature\Geo;

use App\Models\Branch;
use App\Models\Geo\GeoDistrict;
use App\Models\Geo\GeoDivision;
use App\Models\Geo\GeoUnion;
use App\Models\Geo\GeoUpazila;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\PatientAddress;
use App\Models\User;
use App\Services\Geo\GeoInstaller;
use App\Services\Geo\GeoOverview;
use App\Services\Geo\GeoService;
use App\Services\Patients\PatientService;
use App\Services\TenantContextResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/** The address master (B3): the country's places, the lookups, the address on a patient, unions, and where patients come from. */
class GeographyTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    private User $user;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->organization = Organization::factory()->create();
        $this->user = $this->person(['patients.view', 'patients.update', 'settings.view', 'settings.update']);
        $this->actingAs($this->user);
        $branch = Branch::factory()->create(['organization_id' => $this->organization->id]);
        $this->user->branches()->attach($branch->id, ['organization_id' => $this->organization->id]);
        session()->put(['tenant_organization_id' => $this->organization->id, 'tenant_branch_id' => $branch->id]);
        app(TenantContextResolver::class)->setOrganizationId($this->organization->id);
        $this->patient = Patient::factory()->create(['organization_id' => $this->organization->id]);
        app(GeoInstaller::class)->install();
    }

    /** @param  list<string>  $rights */
    private function person(array $rights): User
    {
        $user = User::factory()->create();
        $user->organizations()->attach($this->organization->id, ['access_level' => 'admin']);
        Branch::query()->where('organization_id', $this->organization->id)->each(fn ($b) => $user->branches()->syncWithoutDetaching([$b->id => ['organization_id' => $this->organization->id]]));
        foreach ($rights as $right) {
            Permission::firstOrCreate(['name' => $right, 'guard_name' => 'web']);
            $user->givePermissionTo($right);
        }

        return $user;
    }

    private function place(string $district, ?string $upazila = null): array
    {
        $d = GeoDistrict::query()->where('name_en', $district)->firstOrFail();
        $u = $upazila ? $d->upazilas()->where('name_en', $upazila)->firstOrFail() : $d->upazilas()->firstOrFail();

        return [$d->division_id, $d->id, $u->id];
    }

    // ---- the data ----------------------------------------------------------------------------------------------------------------------------

    public function test_the_country_has_eight_divisions_sixty_four_districts_and_the_upazilas_each_in_its_district(): void
    {
        $this->assertSame([8, 64, 494], [GeoDivision::count(), GeoDistrict::count(), GeoUpazila::count()]);
        $this->assertSame(0, GeoUpazila::query()->whereDoesntHave('district')->count());
        $this->assertSame(0, GeoDistrict::query()->whereDoesntHave('upazilas')->count(), 'every district has upazilas');
        $this->assertSame(0, GeoDivision::query()->where('name_bn', '')->count() + GeoDistrict::query()->where('name_bn', '')->count() + GeoUpazila::query()->where('name_bn', '')->count(), 'every place has a Bangla name');
        $this->assertSame('ঢাকা', GeoDistrict::query()->where('name_en', 'Dhaka')->value('name_bn'));
        $this->assertSame('Dhaka', GeoDistrict::query()->where('name_en', 'Dhaka')->firstOrFail()->division->name_en);
    }

    public function test_installing_again_adds_nothing_and_the_command_says_so(): void
    {
        $this->assertSame(0, app(GeoInstaller::class)->install()['created']);
        $this->assertSame(0, Artisan::call('geo:install'));
        $this->assertSame(494, GeoUpazila::count());
    }

    public function test_a_missing_file_is_refused_not_ignored(): void
    {
        $this->expectException(\RuntimeException::class);

        app(GeoInstaller::class)->install('/nonexistent/geo.json');
    }

    // ---- the lookups -------------------------------------------------------------------------------------------------------------------------

    public function test_a_district_list_waits_for_its_division_and_holds_only_that_divisions_districts(): void
    {
        $dhaka = GeoDivision::query()->where('name_en', 'Dhaka')->firstOrFail();

        $this->getJson('/admin/lookup/geo/district')->assertOk()->assertExactJson([]);
        $rows = $this->getJson('/admin/lookup/geo/district?division_id='.$dhaka->id)->assertOk()->json();

        $this->assertSame($dhaka->districts()->count(), count($rows));
        $this->assertContains('Gazipur · গাজীপুর', array_column($rows, 'text'));
        $this->assertNotContains('Sylhet', array_map(fn ($t) => explode(' ·', $t)[0], array_column($rows, 'text')));
    }

    public function test_places_are_found_by_their_english_or_their_bangla_name(): void
    {
        $en = $this->getJson('/admin/lookup/geo/division?q=dha')->assertOk()->json();
        $bn = $this->getJson('/admin/lookup/geo/division?q='.urlencode('ঢাকা'))->assertOk()->json();

        $this->assertSame('Dhaka · ঢাকা', $en[0]['text']);
        $this->assertSame($en[0]['value'], $bn[0]['value']);
        $this->assertSame([], $this->getJson('/admin/lookup/geo/division?q=%25')->json(), 'a percent sign is a letter, not a wildcard');
    }

    public function test_the_lookup_needs_a_right_that_picks_places_and_knows_only_four_levels(): void
    {
        $this->getJson('/admin/lookup/geo/planet')->assertNotFound();
        $this->actingAs($this->person(['reports.view']));

        $this->getJson('/admin/lookup/geo/division')->assertForbidden();
    }

    // ---- the address on a patient ----------------------------------------------------------------------------------------------------------

    public function test_an_address_keeps_the_places_chosen_and_keeps_the_older_district_text_filled(): void
    {
        [$division, $district, $upazila] = $this->place('Dhaka', 'Savar');
        $union = app(GeoService::class)->addUnion($upazila, 'Ashulia', 'আশুলিয়া');

        $this->post("/admin/patients/{$this->patient->public_id}/addresses", ['address_type' => 'permanent', 'division_id' => $division, 'district_id' => $district, 'upazila_id' => $upazila, 'union_id' => $union->id, 'village_area' => 'Uttar Para', 'is_primary' => true])->assertRedirect();

        $a = PatientAddress::query()->where('patient_id', $this->patient->id)->firstOrFail();
        $this->assertSame([$division, $district, $upazila, $union->id, 'Uttar Para', 'Dhaka'], [$a->division_id, $a->district_id, $a->upazila_id, $a->union_id, $a->village_area, $a->district]);
    }

    public function test_choosing_only_the_lowest_place_fills_in_the_ones_above(): void
    {
        [$division, $district, $upazila] = $this->place('Sylhet');

        $this->post("/admin/patients/{$this->patient->public_id}/addresses", ['address_type' => 'present', 'upazila_id' => $upazila])->assertRedirect();

        $a = PatientAddress::query()->firstOrFail();
        $this->assertSame([$division, $district, $upazila], [$a->division_id, $a->district_id, $a->upazila_id]);
    }

    public function test_places_that_do_not_belong_together_are_refused(): void
    {
        [, $dhakaDistrict] = $this->place('Dhaka');
        [$sylhetDivision] = $this->place('Sylhet');

        $this->post("/admin/patients/{$this->patient->public_id}/addresses", ['address_type' => 'present', 'division_id' => $sylhetDivision, 'district_id' => $dhakaDistrict])->assertSessionHasErrors('district_id');
        $this->assertSame(0, PatientAddress::count());
    }

    public function test_the_address_page_offers_the_four_searchable_levels_each_waiting_for_the_one_above(): void
    {
        $html = $this->get("/admin/patients/{$this->patient->public_id}/addresses")->assertOk()->getContent();

        foreach (['division', 'district', 'upazila', 'union'] as $level) {
            $this->assertStringContainsString('data-remote="'.url('/admin/lookup/geo/'.$level).'"', $html);
        }
        $this->assertStringContainsString('data-remote-depends="#addr-division"', $html);
        $this->assertStringContainsString('data-remote-depends="#addr-upazila"', $html);
    }

    // ---- unions ----------------------------------------------------------------------------------------------------------------------------

    public function test_a_union_is_added_once_and_a_second_time_updates_it(): void
    {
        [, , $upazila] = $this->place('Dhaka', 'Savar');

        $this->post('/admin/master-data/geography/unions', ['upazila_id' => $upazila, 'name_en' => 'Birulia'])->assertRedirect();
        $this->post('/admin/master-data/geography/unions', ['upazila_id' => $upazila, 'name_en' => 'Birulia', 'name_bn' => 'বিরুলিয়া'])->assertRedirect();

        $this->assertSame(1, GeoUnion::count());
        $this->assertSame('বিরুলিয়া', GeoUnion::firstOrFail()->name_bn);
    }

    public function test_a_csv_of_unions_is_imported_and_a_row_that_cannot_be_placed_is_reported_not_guessed(): void
    {
        $csv = "division,district,upazila,union_en,union_bn\nDhaka,Dhaka,Savar,Aminbazar,আমিনবাজার\ndhaka,DHAKA,savar,Ashulia,আশুলিয়া\nDhaka,Dhaka,Nowhere,Ghost,ভূত\nDhaka,Dhaka,Savar,,\n";
        $file = tempnam(sys_get_temp_dir(), 'geo');
        file_put_contents($file, $csv);

        $r = app(GeoService::class)->importUnions($file);
        $again = app(GeoService::class)->importUnions($file);

        $this->assertSame([2, 0, 2], [$r['added'], $r['updated'], count($r['skipped'])]);
        $this->assertSame([0, 2], [$again['added'], $again['updated']]);
        $this->assertSame('import', GeoUnion::query()->where('name_en', 'Aminbazar')->value('source'));
    }

    public function test_an_import_with_the_wrong_columns_is_refused(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'geo');
        file_put_contents($file, "a,b\n1,2\n");

        $this->expectException(ValidationException::class);

        app(GeoService::class)->importUnions($file);
    }

    public function test_importing_and_adding_need_the_right_to_change_settings(): void
    {
        $this->actingAs($this->person(['settings.view']));
        [, , $upazila] = $this->place('Dhaka');

        $this->post('/admin/master-data/geography/unions', ['upazila_id' => $upazila, 'name_en' => 'X'])->assertForbidden();
        $this->post('/admin/master-data/geography/unions/import', ['file' => UploadedFile::fake()->createWithContent('u.csv', "division,district,upazila,union_en\n")])->assertForbidden();
        $this->assertSame(0, GeoUnion::count());
    }

    // ---- older addresses -----------------------------------------------------------------------------------------------------------------------

    public function test_older_addresses_with_only_a_typed_district_are_linked_to_it(): void
    {
        $make = fn (string $text) => PatientAddress::query()->create(['organization_id' => $this->organization->id, 'patient_id' => $this->patient->id, 'address_type' => 'present', 'district' => $text]);
        $a = $make(' dhaka ');
        $b = $make('ঢাকা');
        $c = $make('Atlantis');

        $r = app(GeoService::class)->linkTypedDistricts();

        $this->assertSame([2, 1], [$r['linked'], $r['unmatched']]);
        $this->assertSame($a->fresh()->district_id, $b->fresh()->district_id);
        $this->assertNotNull($a->fresh()->division_id);
        $this->assertNull($c->fresh()->district_id);
        $this->assertSame(0, Artisan::call('geo:link-addresses'));
    }

    // ---- where patients come from ----------------------------------------------------------------------------------------------------------

    public function test_the_page_counts_patients_by_home_district_for_this_hospital_only(): void
    {
        [$dv, $dd, $du] = $this->place('Dhaka');
        [$sv, $sd, $su] = $this->place('Sylhet');
        $svc = app(PatientService::class);
        foreach ([[$dv, $dd, $du], [$dv, $dd, $du], [$sv, $sd, $su]] as [$v, $d, $u]) {
            $svc->addAddress(Patient::factory()->create(['organization_id' => $this->organization->id]), ['address_type' => 'permanent', 'division_id' => $v, 'district_id' => $d, 'upazila_id' => $u]);
        }
        $other = Organization::factory()->create();
        $svc->addAddress(Patient::factory()->create(['organization_id' => $other->id]), ['address_type' => 'permanent', 'division_id' => $dv, 'district_id' => $dd, 'upazila_id' => $du]);
        $svc->addAddress($this->patient, ['address_type' => 'present', 'division_id' => $dv, 'district_id' => $dd, 'upazila_id' => $du]);

        $s = app(GeoOverview::class)->summary($this->organization->id);

        $this->assertSame([['district' => 'Dhaka', 'patients' => 2], ['district' => 'Sylhet', 'patients' => 1]], $s['by_district'], 'permanent addresses of this hospital; a present address and another hospital do not count');
        $this->assertSame(2, $s['districts_served']);
        $this->assertSame(3, $s['with_place']);
        $this->assertSame(4, $s['patients']);
        $this->assertSame(75.0, $s['with_place_percent']);
    }

    public function test_the_page_states_its_question_and_says_when_there_are_no_unions(): void
    {
        $html = $this->get('/admin/master-data/geography')->assertOk()->getContent();

        $this->assertStringContainsString('data-dashboard-question', $html);
        $this->assertStringContainsString('data-dashboard-asof', $html);
        $this->assertStringContainsString('No unions are listed yet.', $html);
        $this->assertStringContainsString('Add a union', $html);
    }

    public function test_the_page_is_for_people_who_may_see_master_data_and_only_they_who_may_change_it_see_the_forms(): void
    {
        $this->actingAs($this->person(['settings.view']));
        $html = $this->get('/admin/master-data/geography')->assertOk()->getContent();
        $this->assertStringNotContainsString('Add a union', $html);

        $this->actingAs($this->person(['patients.view']));
        $this->get('/admin/master-data/geography')->assertForbidden();
    }
}
