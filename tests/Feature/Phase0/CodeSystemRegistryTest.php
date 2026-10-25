<?php

namespace Tests\Feature\Phase0;

use App\Models\Organization;
use App\Models\Terminology\CodeConcept;
use App\Models\Terminology\CodeMap;
use App\Models\Terminology\CodeSystem;
use App\Models\User;
use App\Services\Terminology\CodeImporter;
use App\Services\Terminology\CodeSearch;
use Database\Seeders\CodeSystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CodeSystemRegistryTest extends TestCase
{
    use RefreshDatabase;

    private function csv(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'codes');
        file_put_contents($path, $content);

        return $path;
    }

    public function test_seeder_registers_the_systems_and_only_ucum_units(): void
    {
        $this->seed(CodeSystemSeeder::class);

        $this->assertSame(7, CodeSystem::count());
        $this->assertSame(0, CodeConcept::whereHas('system', fn ($q) => $q->where('key', 'icd10'))->count(), 'ICD codes must come from the official file');
        $this->assertTrue(CodeConcept::where('code', 'mm[Hg]')->exists());
    }

    public function test_import_is_repeatable_and_skips_incomplete_rows(): void
    {
        $this->seed(CodeSystemSeeder::class);
        $system = CodeSystem::where('key', 'icd10')->first();
        $file = $this->csv("\xEF\xBB\xBFcode,display_en\nA00,Cholera\nA01,Typhoid and paratyphoid fevers\n,No code\n");

        $first = app(CodeImporter::class)->importConcepts($system, $file, version: '2019');
        app(CodeImporter::class)->importConcepts($system, $file);

        $this->assertSame(['rows' => 2, 'skipped' => 1], $first);
        $this->assertSame(2, $system->concepts()->count());
        $this->assertSame('2019', $system->fresh()->version);
    }

    public function test_import_rejects_a_file_without_the_expected_columns(): void
    {
        $this->seed(CodeSystemSeeder::class);

        $this->expectException(\InvalidArgumentException::class);

        app(CodeImporter::class)->importConcepts(CodeSystem::where('key', 'atc')->first(), $this->csv("foo,bar\n1,2\n"));
    }

    public function test_maps_only_link_codes_that_exist_in_both_systems(): void
    {
        $this->seed(CodeSystemSeeder::class);
        $icd10 = CodeSystem::where('key', 'icd10')->first();
        $icd11 = CodeSystem::where('key', 'icd11')->first();
        $importer = app(CodeImporter::class);
        $importer->importConcepts($icd10, $this->csv("code,display_en\nA00,Cholera\n"));
        $importer->importConcepts($icd11, $this->csv("code,display_en\n1A00,Cholera\n"));

        $result = $importer->importMaps($icd10, $icd11, $this->csv("from_code,to_code,relationship\nA00,1A00,equivalent\nA00,ZZZ,equivalent\n"));

        $this->assertSame(['rows' => 1, 'skipped' => 1], $result);
        $this->assertSame(1, CodeMap::count());
    }

    public function test_search_prefers_exact_and_prefix_code_matches_then_display_text(): void
    {
        $this->seed(CodeSystemSeeder::class);
        $loinc = CodeSystem::where('key', 'loinc')->first();
        app(CodeImporter::class)->importConcepts($loinc, $this->csv("code,display_en\n718-7,Hemoglobin [Mass/volume] in Blood\n7180-1,Other\n2160-0,Creatinine [Mass/volume] in Serum or Plasma\n"));

        $byCode = app(CodeSearch::class)->search('loinc', '718-7');
        $byText = app(CodeSearch::class)->search('loinc', 'creatinine');

        $this->assertSame('718-7', $byCode->first()['code']);
        $this->assertSame('2160-0', $byText->first()['code']);
    }

    public function test_search_endpoint_requires_login_and_returns_matches(): void
    {
        $this->seed(CodeSystemSeeder::class);
        $this->getJson('/api/v1/codes/search?system=ucum&q=mg')->assertUnauthorized();

        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $user->organizations()->attach($organization->id, ['access_level' => 'staff']);

        $this->actingAs($user, 'sanctum')
            ->withHeader('X-Organization-Id', (string) $organization->id)
            ->getJson('/api/v1/codes/search?system=ucum&q=mg')
            ->assertOk()
            ->assertJsonFragment(['code' => 'mg']);
    }
}
