<?php

namespace Tests\Feature\Patients;

use App\Models\Organization;
use App\Models\Patient;
use App\Models\User;
use App\Services\Search\GlobalSearchService;
use App\Services\Search\Normalizer;
use App\Services\Search\ScanResolver;
use App\Services\TenantContextResolver;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/** H-09: one box for MRN, any phone format, NID, Bangla/English names (sound-alike tolerant), and scanned codes. */
class GlobalSearchTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->organization = Organization::factory()->create();
        $this->user = $this->member($this->organization, ['patients.view']);
        $this->actingAs($this->user);
        app(TenantContextResolver::class)->setOrganizationId($this->organization->id);
    }

    private function member(Organization $organization, array $permissions): User
    {
        $user = User::factory()->create();
        $user->organizations()->attach($organization->id, ['access_level' => 'staff']);
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
            $user->givePermissionTo($permission);
        }

        return $user;
    }

    private function patient(array $attributes = [], ?Organization $organization = null): Patient
    {
        return Patient::factory()->create(array_merge([
            'organization_id' => ($organization ?? $this->organization)->id, 'middle_name' => null, 'phone' => null, 'national_identifier' => null,
        ], $attributes));
    }

    private function find(string $term): array
    {
        return app(GlobalSearchService::class)->search($this->user, $term)->pluck('id')->all();
    }

    public function test_normalizer_makes_every_phone_format_the_same(): void
    {
        foreach (['01712345678', '+8801712345678', '880 1712-345678', '০১৭১২৩৪৫৬৭৮', '(017) 1234 5678'] as $format) {
            $this->assertSame('1712345678', Normalizer::phone($format), $format);
        }
    }

    public function test_finds_by_mobile_in_any_format_including_prefix_while_typing(): void
    {
        $patient = $this->patient(['phone' => '+8801712345678']);
        $this->patient(['phone' => '01899999999']);

        foreach (['01712345678', '8801712345678', '১৭১২৩৪৫৬৭৮', '017123456'] as $typed) {
            $this->assertSame([$patient->public_id], $this->find($typed), $typed);
        }
    }

    public function test_finds_by_mrn_with_or_without_punctuation_and_by_trailing_number(): void
    {
        $patient = $this->patient(['enterprise_patient_no' => 'DHC-00000123']);
        $this->patient(['enterprise_patient_no' => 'DHC-00000456']);

        foreach (['DHC-00000123', 'dhc00000123', '123'] as $typed) {
            $this->assertSame([$patient->public_id], $this->find($typed), $typed);
        }
    }

    public function test_finds_by_national_id(): void
    {
        $patient = $this->patient(['national_identifier' => '1990123456789']);

        $this->assertSame([$patient->public_id], $this->find('1990123456789'));
    }

    public function test_finds_by_name_in_any_word_order_prefix_and_bangla(): void
    {
        $latin = $this->patient(['first_name' => 'Rahim', 'last_name' => 'Uddin']);
        $bangla = $this->patient(['first_name' => 'করিম', 'last_name' => 'হোসেন']);

        $this->assertSame([$latin->public_id], $this->find('uddin rahim'));
        $this->assertSame([$latin->public_id], $this->find('rah'));
        $this->assertSame([$bangla->public_id], $this->find('করিম'));
        $this->assertSame([$bangla->public_id], $this->find('হোসেন করিম'));
        $this->assertSame([], $this->find('rahim hossain'), 'every word must match');
    }

    public function test_sound_alike_spellings_match(): void
    {
        $patient = $this->patient(['first_name' => 'Mohammad', 'last_name' => 'Rahman']);

        $this->assertContains($patient->public_id, $this->find('Muhammad'));
        $this->assertContains($patient->public_id, $this->find('Rahmaan'));
    }

    public function test_results_never_cross_organizations_and_need_the_view_permission(): void
    {
        $this->patient(['first_name' => 'Outsider', 'phone' => '01711111111'], Organization::factory()->create());
        $mine = $this->patient(['first_name' => 'Outsider']);

        $this->assertSame([$mine->public_id], $this->find('outsider'));
        $this->assertSame([], $this->find('01711111111'));

        $this->expectException(AuthorizationException::class);
        app(GlobalSearchService::class)->search($this->member($this->organization, []), 'outsider');
    }

    public function test_index_follows_edits_and_removal(): void
    {
        $patient = $this->patient(['first_name' => 'Karim', 'phone' => '01711000111']);
        $this->assertSame([$patient->public_id], $this->find('01711000111'));

        $patient->update(['phone' => '01822000222', 'first_name' => 'Jamal']);
        $this->assertSame([], $this->find('01711000111'));
        $this->assertSame([$patient->public_id], $this->find('01822000222'));
        $this->assertSame([$patient->public_id], $this->find('jamal'));

        $patient->delete();
        $this->assertSame([], $this->find('jamal'));
    }

    public function test_scan_resolves_wristband_mrn_and_document_numbers_but_only_where_allowed(): void
    {
        $patient = $this->patient(['enterprise_patient_no' => 'DHC-00000777']);
        $scan = app(ScanResolver::class);

        $byMrn = $scan->resolve($this->user, 'DHC-00000777');
        $byUlid = $scan->resolve($this->user, $patient->public_id);

        $this->assertSame('patient', $byMrn['type']);
        $this->assertSame($byMrn, $byUlid);
        $this->assertNull($scan->resolve($this->user, 'NOPE-1'));
        $this->assertNull($scan->resolve($this->member($this->organization, []), 'DHC-00000777'), 'no permission, no answer');
    }

    public function test_api_and_page(): void
    {
        $patient = $this->patient(['first_name' => 'Jahid', 'enterprise_patient_no' => 'DHC-00000999']);
        $this->withHeader('X-Organization-Id', (string) $this->organization->id);

        $this->getJson('/api/v1/search?q=jahid')->assertOk()->assertJsonFragment(['id' => $patient->public_id]);
        $this->getJson('/api/v1/scan?code=DHC-00000999')->assertOk()->assertJsonFragment(['type' => 'patient']);
        $this->getJson('/api/v1/scan?code=unknown-code')->assertNotFound();
        $this->get('/admin/search?q=DHC-00000999')->assertRedirect(route('admin.patients.show', $patient));
        $this->get('/admin/search?q=jahid')->assertOk()->assertSee('Jahid');
    }
}
