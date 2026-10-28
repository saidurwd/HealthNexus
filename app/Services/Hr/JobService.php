<?php

namespace App\Services\Hr;

use App\Models\Hr\HrJob;
use App\Models\Hr\HrPosition;
use Illuminate\Validation\ValidationException;

/** Jobs (what the work is, which licences it needs) and positions (a job in a department, with an authorized headcount). */
class JobService
{
    public function __construct(private readonly HrPolicy $policy) {}

    /** @param  list<string>  $requiredCredentials */
    public function createJob(int $organizationId, string $code, string $title, string $family, ?string $grade = null, array $requiredCredentials = []): HrJob
    {
        if (! in_array($family, (array) $this->policy->get('job_families'), true)) {
            throw ValidationException::withMessages(['family' => 'Not a known job family.']);
        }
        foreach ($requiredCredentials as $type) {
            if (! in_array($type, (array) $this->policy->get('credential_types'), true)) {
                throw ValidationException::withMessages(['required_credentials' => "[$type] is not a known credential type."]);
            }
        }
        if (HrJob::query()->where(['organization_id' => $organizationId, 'code' => strtoupper($code)])->exists()) {
            throw ValidationException::withMessages(['code' => 'That job code is already used.']);
        }

        return HrJob::create(['organization_id' => $organizationId, 'code' => strtoupper($code), 'title' => $title, 'family' => $family, 'grade' => $grade, 'required_credentials' => $requiredCredentials]);
    }

    public function createPosition(HrJob $job, string $code, string $title, ?int $departmentId, int $headcount = 1): HrPosition
    {
        if ($headcount < 1) {
            throw ValidationException::withMessages(['headcount' => 'A position has at least one seat.']);
        }
        if (HrPosition::query()->where(['organization_id' => $job->organization_id, 'code' => strtoupper($code)])->exists()) {
            throw ValidationException::withMessages(['code' => 'That position code is already used.']);
        }

        return HrPosition::create(['organization_id' => $job->organization_id, 'job_id' => $job->id, 'department_id' => $departmentId, 'code' => strtoupper($code), 'title' => $title, 'headcount' => $headcount]);
    }
}
