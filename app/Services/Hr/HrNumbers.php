<?php

namespace App\Services\Hr;

use App\Models\User;
use App\Services\Numbering\NumberSeriesService;

class HrNumbers
{
    public function __construct(private readonly NumberSeriesService $numbers) {}

    public function employee(int $organizationId, ?User $by = null): string
    {
        return $this->numbers->next('hr.employee', $organizationId, null, prefix: 'EMP', format: '{PREFIX}-{SEQ:5}', userId: $by?->id);
    }

    public function payrollRun(int $organizationId, string $yearMonth, string $type, ?User $by = null): string
    {
        return $this->numbers->next("hr.payroll.$yearMonth.$type", $organizationId, null, prefix: ($type === 'regular' ? 'PAY' : 'SUP').'-'.$yearMonth, format: '{PREFIX}-{SEQ:2}', userId: $by?->id);
    }
}
