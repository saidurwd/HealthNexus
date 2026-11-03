<?php

namespace App\Models\Quality;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QaAssessmentResult extends Model
{
    protected $table = 'qa_assessment_results';

    protected $guarded = ['id'];

    public $timestamps = false;

    protected $casts = ['assessed_at' => 'datetime'];

    /** @return BelongsTo<QaStandardItem, $this> */
    public function item(): BelongsTo
    {
        return $this->belongsTo(QaStandardItem::class, 'item_id');
    }

    /** @return BelongsTo<QaAction, $this> */
    public function action(): BelongsTo
    {
        return $this->belongsTo(QaAction::class, 'action_id');
    }
}
