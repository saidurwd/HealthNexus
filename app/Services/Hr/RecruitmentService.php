<?php

namespace App\Services\Hr;

use App\Models\Hr\HrCandidate;
use App\Models\Hr\HrEmployee;
use App\Models\Hr\HrPosition;
use App\Models\Hr\HrVacancy;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** The recruitment foundation (S03): a vacancy against a position, candidates through their stages, and hiring that creates the employee already in onboarding. */
class RecruitmentService
{
    private const STAGES = ['applied' => ['screened', 'rejected', 'withdrawn'], 'screened' => ['interview', 'rejected', 'withdrawn'], 'interview' => ['offered', 'rejected', 'withdrawn'], 'offered' => ['hired', 'rejected', 'withdrawn'], 'hired' => [], 'rejected' => [], 'withdrawn' => []];

    public function __construct(private readonly EmployeeService $employees, private readonly ChecklistService $checklists) {}

    public function openVacancy(int $organizationId, string $title, ?HrPosition $position, int $openings = 1, ?string $closesOn = null): HrVacancy
    {
        if ($openings < 1) {
            throw ValidationException::withMessages(['openings' => 'At least one opening.']);
        }
        if ($position) {
            $space = $position->headcount - $this->employees->headcount($position)['filled'];
            if ($openings > $space) {
                throw ValidationException::withMessages(['openings' => "The position has only $space free seat(s)."]);
            }
        }

        return HrVacancy::create(['organization_id' => $organizationId, 'position_id' => $position?->id, 'department_id' => $position?->department_id, 'title' => $title, 'openings' => $openings, 'status' => 'open', 'opened_on' => now()->toDateString(), 'closes_on' => $closesOn]);
    }

    public function addCandidate(HrVacancy $vacancy, string $name, ?string $phone = null, ?string $email = null): HrCandidate
    {
        if ($vacancy->status !== 'open') {
            throw ValidationException::withMessages(['vacancy' => 'That vacancy is not open.']);
        }

        return HrCandidate::create(['organization_id' => $vacancy->organization_id, 'vacancy_id' => $vacancy->id, 'name' => $name, 'phone' => $phone, 'email' => $email])->refresh();
    }

    public function advance(HrCandidate $candidate, string $stage, ?string $notes = null): HrCandidate
    {
        if (! in_array($stage, self::STAGES[$candidate->stage] ?? [], true)) {
            throw ValidationException::withMessages(['stage' => "A candidate who is {$candidate->stage} cannot become $stage."]);
        }
        if ($stage === 'hired') {
            throw ValidationException::withMessages(['stage' => 'Use hire() to employ a candidate.']);
        }
        $candidate->update(['stage' => $stage, 'notes' => $notes ?? $candidate->notes]);

        return $candidate->refresh();
    }

    /** @param  array<string, mixed>  $employeeData  at least first_name; joined_on, department, job, position … */
    public function hire(HrCandidate $candidate, User $by, array $employeeData): HrEmployee
    {
        if ($candidate->stage !== 'offered') {
            throw ValidationException::withMessages(['stage' => 'Only a candidate who has been offered the job can be hired.']);
        }

        return DB::transaction(function () use ($candidate, $by, $employeeData) {
            $vacancy = HrVacancy::query()->lockForUpdate()->findOrFail($candidate->vacancy_id);
            $position = $vacancy->position_id ? HrPosition::find($vacancy->position_id) : null;
            $employee = $this->employees->create($by, $candidate->organization_id, $employeeData + ['first_name' => $candidate->name, 'phone' => $candidate->phone, 'email' => $candidate->email, 'department_id' => $vacancy->department_id, 'position_id' => $position?->id, 'job_id' => $position?->job_id]);
            $this->checklists->open($employee, 'onboarding', $by);
            $candidate->update(['stage' => 'hired', 'hired_employee_id' => $employee->id]);
            if (HrCandidate::query()->where(['vacancy_id' => $vacancy->id, 'stage' => 'hired'])->count() >= $vacancy->openings) {
                $vacancy->update(['status' => 'filled']);
                HrCandidate::query()->where('vacancy_id', $vacancy->id)->whereNotIn('stage', ['hired', 'rejected', 'withdrawn'])->update(['stage' => 'rejected', 'notes' => 'Vacancy filled']);
            }

            return $employee;
        });
    }
}
