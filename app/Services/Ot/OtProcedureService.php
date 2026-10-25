<?php

namespace App\Services\Ot;

use App\Models\Ot\OtProcedure;
use App\Models\Ot\OtProcedureCategory;

class OtProcedureService
{
    public function createCategory(array $data): OtProcedureCategory
    {
        return OtProcedureCategory::create($data);
    }

    public function create(array $data): OtProcedure
    {
        return OtProcedure::create($data);
    }

    public function update(OtProcedure $procedure, array $data): OtProcedure
    {
        $procedure->update($data);

        return $procedure->refresh();
    }
}
