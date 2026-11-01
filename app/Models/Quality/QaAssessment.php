<?php

namespace App\Models\Quality;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QaAssessment extends Model
{
    use HasPublicId;

    protected $table = 'qa_assessments';

    protected $guarded = ['id'];

    protected $casts = ['started_on' => 'date:Y-m-d', 'completed_on' => 'date:Y-m-d', 'score' => 'decimal:2'];

    /** @return BelongsTo<QaStandard, $this> */
    public function standard(): BelongsTo
    {
        return $this->belongsTo(QaStandard::class, 'standard_id');
    }

    /** @return HasMany<QaAssessmentResult, $this> */
    public function results(): HasMany
    {
        return $this->hasMany(QaAssessmentResult::class, 'assessment_id');
    }
}
