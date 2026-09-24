<?php

namespace App\Models\Nursing;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NursingCarePlanIntervention extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_DISCONTINUED = 'discontinued';

    protected $fillable = [
        'care_plan_id', 'goal_id', 'intervention_type', 'frequency', 'responsible_nurse_id',
        'status', 'evaluation_notes',
    ];

    public function carePlan(): BelongsTo
    {
        return $this->belongsTo(NursingCarePlan::class, 'care_plan_id');
    }

    public function goal(): BelongsTo
    {
        return $this->belongsTo(NursingCarePlanGoal::class, 'goal_id');
    }

    public function responsibleNurse(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_nurse_id');
    }
}
