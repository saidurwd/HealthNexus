<?php

namespace App\Services\Facility;

/** What a hospital starts with: diet types, linen types, a mortuary unit and a few instrument sets. All examples to edit. Safe to run again. */
class FacilityInstaller
{
    public function __construct(private readonly DietService $diet, private readonly LinenService $linen, private readonly MortuaryService $mortuary, private readonly CssdService $cssd) {}

    /** @return array{diets:int,linen:int,units:int,sets:int} */
    public function install(int $organizationId): array
    {
        foreach ([['NORMAL', 'Normal diet'], ['DIABETIC', 'Diabetic diet'], ['LOWSALT', 'Low-salt diet'], ['RENAL', 'Renal diet'], ['SOFT', 'Soft diet'], ['LIQUID', 'Liquid diet'], ['PAEDIATRIC', 'Children\'s diet'], ['HIGHPROT', 'High-protein diet']] as [$c, $n]) {
            $this->diet->type($organizationId, $c, $n);
        }
        foreach ([['SHEET', 'Bed sheet', 120], ['PILLOW', 'Pillow cover', 80], ['BLANKET', 'Blanket', 60], ['GOWN', 'Patient gown', 90], ['TOWEL', 'Towel', 60], ['DRAPE', 'Theatre drape', 40]] as [$c, $n, $p]) {
            $this->linen->type($organizationId, $c, $n, $p);
        }
        $this->mortuary->unit($organizationId, 'MC-1', 'Cold chamber 1', 4);
        $this->mortuary->unit($organizationId, 'MC-2', 'Cold chamber 2', 4);
        foreach ([['GEN-SURG', 'General surgery set', 32], ['MINOR', 'Minor procedure set', 14], ['CSECT', 'Caesarean section set', 28], ['DNC', 'D and C set', 18], ['ORTHO', 'Orthopaedic basic set', 40], ['DRESS', 'Dressing set', 8]] as [$c, $n, $k]) {
            $this->cssd->set($organizationId, $c, $n, $k);
        }

        return ['diets' => 8, 'linen' => 6, 'units' => 2, 'sets' => 6];
    }
}
