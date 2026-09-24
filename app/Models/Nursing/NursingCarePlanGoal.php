<?php

namespace App\Models\Nursing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NursingCarePlanGoal extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_MET = 'met';

    public const STATUS_PARTIALLY_MET = 'partially_met';

    public const STATUS_NOT_MET = 'not_met';

    protected $fillable = ['care_plan_id', 'nursing_diagnosis_id', 'goal_text', 'target_date', 'status'];

    protected function casts(): array
    {
        return ['target_date' => 'date'];
    }

    public function carePlan(): BelongsTo
    {
        return $this->belongsTo(NursingCarePlan::class, 'care_plan_id');
    }

    public function diagnosis(): BelongsTo
    {
        return $this->belongsTo(NursingDiagnosis::class, 'nursing_diagnosis_id');
    }
}
