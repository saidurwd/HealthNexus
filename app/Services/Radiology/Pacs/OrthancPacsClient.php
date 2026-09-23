<?php

namespace App\Services\Radiology\Pacs;

use App\Contracts\Radiology\PacsClientInterface;
use App\Models\Radiology\RadiologyPacsServer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Orthanc REST API adapter (https://orthanc.uclouvain.be/book/users/rest.html). Uses Laravel's
 * built-in Http facade (Guzzle under the hood) — no new composer dependency. Every call is
 * defensive: PACS unavailability/timeouts never bubble up as an unhandled exception to the
 * caller, they return null/empty per the interface contract (spec §80).
 */
class OrthancPacsClient implements PacsClientInterface
{
    public function __construct(private readonly RadiologyPacsServer $server) {}

    public function findStudy(string $accessionNumber): ?array
    {
        $matches = $this->queryStudies(['accession_number' => $accessionNumber]);

        if (empty($matches)) {
            return null;
        }

        $match = $matches[0];
        $metadata = $this->getStudyMetadata($match['study_instance_uid']);

        return [
            'study_instance_uid' => $match['study_instance_uid'],
            'study_date' => $match['study_date'],
            'modality' => $match['modality'],
            'study_description' => $match['study_description'],
            'number_of_series' => count($metadata['series'] ?? []),
            'number_of_instances' => array_sum(array_column($metadata['series'] ?? [], 'number_of_instances')),
        ];
    }

    public function queryStudies(array $criteria): array
    {
        $query = array_filter([
            'AccessionNumber' => $criteria['accession_number'] ?? null,
            'PatientID' => $criteria['patient_id'] ?? null,
            'Modality' => $criteria['modality'] ?? null,
            'StudyDate' => $criteria['study_date'] ?? null,
        ]);

        $response = $this->request()->post('/tools/find', [
            'Level' => 'Study',
            'Query' => (object) $query,
            'Expand' => true,
        ]);

        if (! $response->successful()) {
            $this->logFailure('queryStudies', $response->status(), $response->body());

            return [];
        }

        return collect($response->json() ?? [])
            ->map(function (array $study) {
                $tags = $study['MainDicomTags'] ?? [];

                return [
                    'study_instance_uid' => $tags['StudyInstanceUID'] ?? null,
                    'accession_number' => $tags['AccessionNumber'] ?? null,
                    'study_date' => $tags['StudyDate'] ?? null,
                    'modality' => $tags['ModalitiesInStudy'] ?? $tags['Modality'] ?? null,
                    'study_description' => $tags['StudyDescription'] ?? null,
                ];
            })
            ->filter(fn (array $study) => $study['study_instance_uid'] !== null)
            ->values()
            ->all();
    }

    public function getStudyMetadata(string $studyInstanceUid): ?array
    {
        // queryStudies() searches by accession/patient/modality/date — resolve the Orthanc-
        // internal ID via a direct StudyInstanceUID lookup instead.
        $response = $this->request()->post('/tools/find', [
            'Level' => 'Study',
            'Query' => ['StudyInstanceUID' => $studyInstanceUid],
        ]);

        if (! $response->successful() || empty($response->json())) {
            $this->logFailure('getStudyMetadata', $response->status(), $response->body());

            return null;
        }

        $orthancId = $response->json()[0];

        $seriesResponse = $this->request()->get("/studies/{$orthancId}/series");

        if (! $seriesResponse->successful()) {
            $this->logFailure('getStudyMetadata:series', $seriesResponse->status(), $seriesResponse->body());

            return ['series' => []];
        }

        $series = collect($seriesResponse->json() ?? [])->map(function (array $s) {
            $tags = $s['MainDicomTags'] ?? [];

            return [
                'series_instance_uid' => $tags['SeriesInstanceUID'] ?? null,
                'series_number' => $tags['SeriesNumber'] ?? null,
                'series_description' => $tags['SeriesDescription'] ?? null,
                'number_of_instances' => count($s['Instances'] ?? []),
            ];
        })->filter(fn (array $s) => $s['series_instance_uid'] !== null)->values()->all();

        return ['series' => $series];
    }

    public function getViewerUrl(string $studyInstanceUid): ?string
    {
        if (! $this->server->base_url) {
            return null;
        }

        // OHIF-plugin convention — configurable per install; adapt the path if the server's
        // OHIF/viewer plugin is mounted elsewhere.
        return rtrim($this->server->base_url, '/')."/ohif/viewer?StudyInstanceUIDs={$studyInstanceUid}";
    }

    private function request()
    {
        $http = Http::baseUrl(rtrim((string) $this->server->base_url, '/'))
            ->acceptJson()
            ->timeout(10)
            ->connectTimeout(5);

        if ($this->server->username) {
            $http = $http->withBasicAuth($this->server->username, (string) $this->server->password);
        }

        return $http;
    }

    private function logFailure(string $operation, int $status, string $body): void
    {
        Log::warning("Radiology PACS ({$this->server->code}) {$operation} failed", [
            'status' => $status,
            'body' => mb_substr($body, 0, 500),
        ]);
    }
}
