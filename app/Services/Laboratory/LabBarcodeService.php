<?php

namespace App\Services\Laboratory;

use App\Models\Laboratory\LabSpecimen;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * Generates a scannable QR code encoding the specimen's accession number, for tube/container
 * labels. Mirrors App\Services\Patients\PatientBarcodeService's SVG-inline approach — no 1D
 * barcode library exists in this project, and QR functionally satisfies the spec's "unique
 * scannable identifier" requirement without adding a new dependency. See Phase 5 plan decision #3.
 */
class LabBarcodeService
{
    public function svg(LabSpecimen $specimen): string
    {
        return QrCode::format('svg')
            ->size(150)
            ->margin(1)
            ->generate($specimen->accession_number);
    }
}
