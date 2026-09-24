<?php

namespace App\Services\Nursing;

use App\Models\Nursing\NursingShift;

class NursingShiftService
{
    public function create(array $data): NursingShift
    {
        return NursingShift::create($data);
    }

    public function update(NursingShift $shift, array $data): NursingShift
    {
        $shift->update($data);

        return $shift->refresh();
    }
}
