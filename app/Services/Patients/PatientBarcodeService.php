<?php

namespace App\Services\Patients;

use App\Models\Patient;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * Generates a scannable QR code encoding the patient's enterprise patient number, for wristbands,
 * ID cards, and the printable patient face-sheet. SVG output avoids depending on a specific image
 * library being configured correctly in every deployment environment.
 */
class PatientBarcodeService
{
    public function svg(Patient $patient): string
    {
        return QrCode::format('svg')
            ->size(200)
            ->margin(1)
            ->generate($patient->enterprise_patient_no ?? (string) $patient->id);
    }
}
