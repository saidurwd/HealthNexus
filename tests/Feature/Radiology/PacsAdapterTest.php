<?php

namespace Tests\Feature\Radiology;

use App\Models\Radiology\RadiologyPacsServer;
use App\Services\Radiology\Pacs\NullPacsClient;
use App\Services\Radiology\Pacs\OrthancPacsClient;
use App\Services\Radiology\Pacs\PacsClientFactory;
use Illuminate\Support\Facades\Http;

/**
 * PACS adapter tests per spec §80 — always against Http::fake(), never a real network call.
 */
class PacsAdapterTest extends RadiologyTestCase
{
    public function test_factory_resolves_null_client_by_default(): void
    {
        $server = RadiologyPacsServer::create([
            'company_id' => $this->company->id, 'branch_id' => null,
            'code' => 'NONE', 'name' => 'Not connected', 'adapter_type' => 'null', 'is_active' => false,
        ]);

        $client = app(PacsClientFactory::class)->for($server);

        $this->assertInstanceOf(NullPacsClient::class, $client);
        $this->assertNull($client->findStudy('ANY-ACCESSION'));
        $this->assertSame([], $client->queryStudies([]));
        $this->assertNull($client->getViewerUrl('1.2.3'));
    }

    public function test_factory_resolves_orthanc_client(): void
    {
        $server = RadiologyPacsServer::create([
            'company_id' => $this->company->id, 'branch_id' => null,
            'code' => 'ORTHANC', 'name' => 'Orthanc', 'adapter_type' => 'orthanc',
            'base_url' => 'https://pacs.test', 'is_active' => true,
        ]);

        $client = app(PacsClientFactory::class)->for($server);

        $this->assertInstanceOf(OrthancPacsClient::class, $client);
    }

    public function test_orthanc_client_finds_a_study_by_accession_number(): void
    {
        Http::fake([
            'pacs.test/tools/find' => Http::sequence()
                ->push([[
                    'MainDicomTags' => [
                        'StudyInstanceUID' => '1.2.840.113619.2.55.1',
                        'AccessionNumber' => 'RAD-2026-00000001',
                        'StudyDate' => '20261001',
                        'Modality' => 'CT',
                        'StudyDescription' => 'CT Brain',
                    ],
                ]])
                ->push(['orthanc-internal-id']),
            'pacs.test/studies/orthanc-internal-id/series' => Http::response([
                [
                    'MainDicomTags' => ['SeriesInstanceUID' => '1.2.840.113619.2.55.2', 'SeriesNumber' => '1', 'SeriesDescription' => 'Axial'],
                    'Instances' => ['a', 'b', 'c'],
                ],
            ]),
        ]);

        $server = RadiologyPacsServer::create([
            'company_id' => $this->company->id, 'branch_id' => null,
            'code' => 'ORTHANC', 'name' => 'Orthanc', 'adapter_type' => 'orthanc',
            'base_url' => 'https://pacs.test', 'is_active' => true,
        ]);

        $client = app(PacsClientFactory::class)->for($server);
        $found = $client->findStudy('RAD-2026-00000001');

        $this->assertNotNull($found);
        $this->assertSame('1.2.840.113619.2.55.1', $found['study_instance_uid']);
        $this->assertSame(1, $found['number_of_series']);
        $this->assertSame(3, $found['number_of_instances']);
    }

    public function test_orthanc_client_returns_null_when_no_study_matches(): void
    {
        Http::fake(['pacs.test/tools/find' => Http::response([])]);

        $server = RadiologyPacsServer::create([
            'company_id' => $this->company->id, 'branch_id' => null,
            'code' => 'ORTHANC', 'name' => 'Orthanc', 'adapter_type' => 'orthanc',
            'base_url' => 'https://pacs.test', 'is_active' => true,
        ]);

        $client = app(PacsClientFactory::class)->for($server);

        $this->assertNull($client->findStudy('NO-SUCH-ACCESSION'));
    }

    public function test_orthanc_client_handles_pacs_unavailability_gracefully(): void
    {
        Http::fake(['pacs.test/tools/find' => Http::response(null, 500)]);

        $server = RadiologyPacsServer::create([
            'company_id' => $this->company->id, 'branch_id' => null,
            'code' => 'ORTHANC', 'name' => 'Orthanc', 'adapter_type' => 'orthanc',
            'base_url' => 'https://pacs.test', 'is_active' => true,
        ]);

        $client = app(PacsClientFactory::class)->for($server);

        $this->assertNull($client->findStudy('RAD-2026-00000001'));
        $this->assertSame([], $client->queryStudies(['accession_number' => 'RAD-2026-00000001']));
    }

    public function test_viewer_url_is_generated_without_exposing_pacs_credentials(): void
    {
        $server = RadiologyPacsServer::create([
            'company_id' => $this->company->id, 'branch_id' => null,
            'code' => 'ORTHANC', 'name' => 'Orthanc', 'adapter_type' => 'orthanc',
            'base_url' => 'https://pacs.test', 'username' => 'secret-user', 'password' => 'secret-pass', 'is_active' => true,
        ]);

        $client = app(PacsClientFactory::class)->for($server);
        $url = $client->getViewerUrl('1.2.840.113619.2.55.1');

        $this->assertStringContainsString('1.2.840.113619.2.55.1', $url);
        $this->assertStringNotContainsString('secret-user', $url);
        $this->assertStringNotContainsString('secret-pass', $url);
    }
}
