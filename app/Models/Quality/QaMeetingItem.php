<?php

namespace App\Models\Quality;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QaMeetingItem extends Model
{
    protected $table = 'qa_meeting_items';

    protected $guarded = ['id'];

    public $timestamps = false;

    /** @return BelongsTo<QaAction, $this> */
    public function action(): BelongsTo
    {
        return $this->belongsTo(QaAction::class, 'action_id');
    }
}
