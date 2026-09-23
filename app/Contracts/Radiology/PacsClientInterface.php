<?php

namespace App\Contracts\Radiology;

/**
 * Vendor-neutral seam for PACS integration (Phase 6 plan decision #2). The RIS domain never
 * depends on a specific PACS vendor directly — only on this interface. No DICOM binary protocol
 * is implemented behind it; only HTTP/JSON query + viewer-URL generation (client-side DICOMweb-
 * style queries), per spec §32/§33.
 */
interface PacsClientInterface
{
    /**
     * @return array{study_instance_uid: string, study_date: ?string, modality: ?string, study_description: ?string, number_of_series: int, number_of_instances: int}|null
     */
    public function findStudy(string $accessionNumber): ?array;

    /**
     * @param  array{patient_id?:string,accession_number?:string,modality?:string,study_date?:string}  $criteria
     * @return array<int, array{study_instance_uid: string, accession_number: ?string, study_date: ?string, modality: ?string, study_description: ?string}>
     */
    public function queryStudies(array $criteria): array;

    /**
     * @return array{series: array<int, array{series_instance_uid: string, series_number: ?string, series_description: ?string, number_of_instances: int}>}|null
     */
    public function getStudyMetadata(string $studyInstanceUid): ?array;

    /**
     * Never returns a raw, unauthenticated PACS URL exposed directly to a browser without the
     * caller having already authorized the request (spec §35/§58) — callers must go through
     * RadiologyStudyService::viewerUrl(), which performs that authorization + audit logging.
     */
    public function getViewerUrl(string $studyInstanceUid): ?string;
}
