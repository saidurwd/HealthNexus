<?php

namespace App\Services\Hr;

use App\Models\Hr\HrAppraisal;
use App\Models\Hr\HrAppraisalCycle;
use App\Models\Hr\HrEmployee;
use App\Models\Hr\HrTraining;
use App\Models\Hr\HrTrainingRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Appraisals and training (S11). Light by design: a rating, two comments, an acknowledgement — and training that counts for a while. */
class PerformanceService
{
    public function __construct(private readonly HrPolicy $policy) {}

    public function openCycle(int $organizationId, string $name, string $start, string $end): HrAppraisalCycle
    {
        if ($end <= $start) {
            throw ValidationException::withMessages(['period_end' => 'The cycle must end after it starts.']);
        }

        return DB::transaction(function () use ($organizationId, $name, $start, $end) {
            $cycle = HrAppraisalCycle::create(['organization_id' => $organizationId, 'name' => $name, 'period_start' => $start, 'period_end' => $end]);
            HrEmployee::query()->where('organization_id', $organizationId)->where('status', 'active')->whereDate('joined_on', '<=', $end)->orderBy('id')->each(fn (HrEmployee $e) => HrAppraisal::create(['organization_id' => $organizationId, 'cycle_id' => $cycle->id, 'employee_id' => $e->id, 'reviewer_id' => $e->manager_id]));

            return $cycle;
        });
    }

    public function submit(HrAppraisal $appraisal, HrEmployee $reviewer, int $rating, ?string $strengths, ?string $improvements): HrAppraisal
    {
        [$min, $max] = (array) $this->policy->get('appraisal_scale');
        if ($rating < $min || $rating > $max) {
            throw ValidationException::withMessages(['rating' => "Rate from $min to $max."]);
        }
        if ($appraisal->status !== 'pending') {
            throw ValidationException::withMessages(['status' => "This appraisal is already {$appraisal->status}."]);
        }
        if ($reviewer->id === $appraisal->employee_id) {
            throw ValidationException::withMessages(['reviewer' => 'Nobody reviews themselves.']);
        }
        $appraisal->update(['reviewer_id' => $reviewer->id, 'rating' => $rating, 'strengths' => $strengths, 'improvements' => $improvements, 'status' => 'submitted', 'submitted_at' => now()]);

        return $appraisal->refresh();
    }

    public function acknowledge(HrAppraisal $appraisal, HrEmployee $employee): HrAppraisal
    {
        if ($appraisal->employee_id !== $employee->id || $appraisal->status !== 'submitted') {
            throw ValidationException::withMessages(['status' => 'You can acknowledge your own submitted appraisal.']);
        }
        $appraisal->update(['status' => 'acknowledged', 'acknowledged_at' => now()]);

        return $appraisal->refresh();
    }

    public function createTraining(int $organizationId, string $title, string $heldOn, bool $mandatory = false, ?int $validMonths = null): HrTraining
    {
        return HrTraining::create(['organization_id' => $organizationId, 'title' => $title, 'held_on' => $heldOn, 'mandatory' => $mandatory, 'valid_months' => $validMonths]);
    }

    public function record(HrTraining $training, HrEmployee $employee, string $outcome = 'attended'): HrTrainingRecord
    {
        if (! in_array($outcome, ['attended', 'passed', 'failed', 'absent'], true)) {
            throw ValidationException::withMessages(['outcome' => 'Not a known outcome.']);
        }

        return HrTrainingRecord::updateOrCreate(['training_id' => $training->id, 'employee_id' => $employee->id], ['organization_id' => $training->organization_id, 'outcome' => $outcome,
            'valid_until' => in_array($outcome, ['attended', 'passed'], true) && $training->valid_months ? $training->held_on->copy()->addMonths($training->valid_months)->toDateString() : null]);
    }

    /** Mandatory trainings the person has no valid record for. @return list<string> */
    public function mandatoryGaps(HrEmployee $employee): array
    {
        $gaps = [];
        foreach (HrTraining::query()->where(['organization_id' => $employee->organization_id, 'mandatory' => true])->get() as $training) {
            $ok = HrTrainingRecord::query()->where(['training_id' => $training->id, 'employee_id' => $employee->id])->whereIn('outcome', ['attended', 'passed'])->where(fn ($q) => $q->whereNull('valid_until')->orWhereDate('valid_until', '>=', now()->toDateString()))->exists();
            $ok || $gaps[] = $training->title;
        }

        return $gaps;
    }
}
