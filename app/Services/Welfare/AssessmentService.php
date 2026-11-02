<?php

namespace App\Services\Welfare;

use App\Models\Patient;
use App\Models\User;
use App\Models\Welfare\WfAssessment;
use App\Services\AuditLogger;
use App\Services\Numbering\NumberSeriesService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The means test. The assessor records income, household size and circumstances; the system works out income per person and a SUGGESTED category with plain, shown arithmetic. A different person (the committee) decides.
 * The thresholds are the hospital's settings (config/welfare.php shows examples); nothing here is a poverty line.
 */
class AssessmentService
{
    public function __construct(private readonly WelfarePolicy $policy, private readonly NumberSeriesService $numbers, private readonly AuditLogger $audit) {}

    /** @param array{monthly_income?:mixed,household_size?:mixed,occupation?:?string,factors?:mixed,evidence_note?:?string} $d */
    public function start(Patient $patient, array $d, User $by): WfAssessment
    {
        $income = $d['monthly_income'] ?? null;
        if ($income === null || $income === '' || ! is_numeric($income) || (float) $income < 0) {
            throw ValidationException::withMessages(['monthly_income' => __('Enter the household income per month (zero if none).')]);
        }
        $size = (int) ($d['household_size'] ?? 0);
        if ($size < 1 || $size > 40) {
            throw ValidationException::withMessages(['household_size' => __('Enter how many people the income supports.')]);
        }
        $known = array_keys((array) config('welfare.factors'));
        $factors = array_values(array_intersect($known, array_map('strval', (array) ($d['factors'] ?? []))));
        if (WfAssessment::query()->where('patient_id', $patient->id)->where(fn ($q) => $q->where('status', 'submitted')->orWhere(fn ($w) => $w->where('status', 'approved')->whereDate('valid_until', '>=', today())))->exists()) {
            throw ValidationException::withMessages(['patient_no' => __('This patient already has an assessment waiting or in force.')]);
        }
        [$perHead, $points, $category, $percent] = $this->work((float) $income, $size, $factors);

        return DB::transaction(function () use ($patient, $d, $by, $income, $size, $factors, $perHead, $points, $category, $percent) {
            $a = WfAssessment::create(['organization_id' => $patient->organization_id, 'number' => $this->numbers->next('wf.assessment', (int) $patient->organization_id, prefix: 'WA', format: '{PREFIX}-{YEAR}-{SEQ:6}', period: NumberSeriesService::PERIOD_YEAR, userId: $by->id),
                'patient_id' => $patient->id, 'assessed_by' => $by->id, 'monthly_income' => number_format((float) $income, 2, '.', ''), 'household_size' => $size, 'occupation' => isset($d['occupation']) ? mb_substr((string) $d['occupation'], 0, 80) : null,
                'factors' => $factors, 'per_head' => number_format($perHead, 2, '.', ''), 'score' => $points, 'category' => $category, 'suggested_percent' => $percent, 'evidence_note' => isset($d['evidence_note']) ? mb_substr((string) $d['evidence_note'], 0, 500) : null, 'status' => 'draft']);
            $this->audit->log('CREATE', WfAssessment::class, $a->id, null, ['patient' => $patient->id, 'category' => $category]);

            return $a;
        });
    }

    /** @return array{0:float,1:int,2:string,3:int} income per head, points, suggested category, suggested percent */
    public function work(float $income, int $size, array $factors): array
    {
        $perHead = round($income / max(1, $size), 2);
        $m = (array) config('welfare.means');
        $free = (float) $this->policy->get('means.free_below_per_head', $m['free_below_per_head']);
        $sub = (float) $this->policy->get('means.subsidised_below_per_head', $m['subsidised_below_per_head']);
        $category = $perHead < $free ? 'free' : ($perHead < $sub ? 'subsidised' : 'none');
        $points = array_sum(array_map(fn ($f) => (int) config("welfare.factors.$f", 0), $factors));
        if ($points >= (int) $this->policy->get('means.points_to_upgrade', $m['points_to_upgrade'])) {
            $category = $category === 'none' ? 'subsidised' : 'free';
        }
        $percent = match ($category) {
            'free' => (int) $this->policy->get('means.free_percent', $m['free_percent']),
            'subsidised' => (int) $this->policy->get('means.subsidised_percent', $m['subsidised_percent']),
            default => 0,
        };

        return [$perHead, $points, $category, $percent];
    }

    public function submit(WfAssessment $a, User $by): WfAssessment
    {
        if ($a->status !== 'draft') {
            throw ValidationException::withMessages(['assessment' => __('Only a draft can be sent to the committee.')]);
        }
        if (mb_strlen(trim((string) $a->evidence_note)) < 10) {
            throw ValidationException::withMessages(['evidence_note' => __('Say what was seen or checked (for example a visit, a letter from the ward member, a card).')]);
        }
        $a->update(['status' => 'submitted']);
        $this->audit->log('UPDATE', WfAssessment::class, $a->id, null, ['status' => 'submitted']);

        return $a;
    }

    public function decide(WfAssessment $a, User $by, bool $approve, ?string $note = null): WfAssessment
    {
        if ($a->status !== 'submitted') {
            throw ValidationException::withMessages(['assessment' => __('This assessment is not waiting for a decision.')]);
        }
        if ($a->assessed_by === $by->id) {
            throw ValidationException::withMessages(['assessment' => __('A second person must decide: the assessor cannot approve their own assessment.')]);
        }
        if (! $approve && mb_strlen(trim((string) $note)) < 5) {
            throw ValidationException::withMessages(['note' => __('Say why it is turned down.')]);
        }
        $a->update(['status' => $approve ? 'approved' : 'rejected', 'decided_by' => $by->id, 'decided_at' => now(), 'decision_note' => $note ? mb_substr($note, 0, 300) : null,
            'valid_until' => $approve ? today()->addDays($this->policy->int('assessment_valid_days', 180)) : null]);
        $this->audit->log('UPDATE', WfAssessment::class, $a->id, null, ['status' => $a->status]);

        return $a;
    }

    /** An approved assessment past its date lapses. @return int how many */
    public function expireDue(int $organizationId): int
    {
        return WfAssessment::query()->where('organization_id', $organizationId)->where('status', 'approved')->whereDate('valid_until', '<', today())->update(['status' => 'expired']);
    }
}
