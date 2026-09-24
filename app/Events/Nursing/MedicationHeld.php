<?php

namespace App\Events\Nursing;

use App\Models\Nursing\NursingMedicationAdministration;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MedicationHeld
{
    use Dispatchable, SerializesModels;

    public function __construct(public NursingMedicationAdministration $administration) {}
}
