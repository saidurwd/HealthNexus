<?php

namespace App\Interop\Dicom;

use App\Models\Integration\DicomWorklistEntry;
use App\Models\Radiology\RadiologyStudy;
use Carbon\Carbon;

/** DICOM JSON (PS3.18 F): every attribute is `{"tag": {"vr": "..", "Value": [..]}}`. Only the attributes a scanner or viewer needs for the worklist and for study lookups. */
final class DicomJson
{
    private static function el(string $vr, mixed $value): array
    {
        return $value === null || $value === '' ? ['vr' => $vr] : ['vr' => $vr, 'Value' => [$value]];
    }

    private static function pn(?string $name): array
    {
        return $name === null || $name === '' ? ['vr' => 'PN'] : ['vr' => 'PN', 'Value' => [['Alphabetic' => $name]]];
    }

    /** @return array<string, mixed> */
    public static function worklistItem(DicomWorklistEntry $e): array
    {
        $start = $e->scheduled_at?->copy()->setTimezone('Asia/Dhaka');

        return [
            '00080005' => self::el('CS', 'ISO_IR 192'),
            '00080050' => self::el('SH', $e->accession_number),
            '00080090' => self::pn($e->referring_physician),
            '00100010' => self::pn($e->patient_name),
            '00100020' => self::el('LO', $e->patient_mrn),
            '00100030' => self::el('DA', $e->birth_date?->format('Ymd')),
            '00100040' => self::el('CS', $e->sex),
            '00321060' => self::el('LO', $e->procedure_description),
            '00400100' => ['vr' => 'SQ', 'Value' => [[
                '00080060' => self::el('CS', $e->modality),
                '00400001' => self::el('AE', $e->station_ae_title),
                '00400002' => self::el('DA', $start?->format('Ymd')),
                '00400003' => self::el('TM', $start?->format('His')),
                '00400007' => self::el('LO', $e->procedure_description),
                '00400009' => self::el('SH', 'SPS'.$e->id),
                '00400020' => self::el('CS', strtoupper($e->status === 'in_progress' ? 'STARTED' : ($e->status === 'completed' ? 'COMPLETED' : 'SCHEDULED'))),
            ]]],
            '00401001' => self::el('SH', $e->requested_procedure_id),
        ];
    }

    /** @return array<string, mixed> */
    public static function study(RadiologyStudy $s, string $patientName, string $mrn): array
    {
        return [
            '0020000D' => self::el('UI', $s->study_instance_uid),
            '00080050' => self::el('SH', $s->accession_number),
            '00080020' => self::el('DA', $s->study_date ? Carbon::parse($s->study_date)->format('Ymd') : null),
            '00080061' => self::el('CS', $s->modality),
            '00081030' => self::el('LO', $s->study_description),
            '00100010' => self::pn($patientName),
            '00100020' => self::el('LO', $mrn),
            '00201206' => self::el('IS', $s->number_of_series),
            '00201208' => self::el('IS', $s->number_of_instances),
        ];
    }
}
