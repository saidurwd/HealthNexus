<?php

namespace App\Models\Quality;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QaMeeting extends Model
{
    use HasPublicId;

    protected $table = 'qa_meetings';

    protected $guarded = ['id'];

    protected $casts = ['scheduled_at' => 'datetime', 'held_at' => 'datetime', 'attendees' => 'array'];

    /** @return BelongsTo<QaCommittee, $this> */
    public function committee(): BelongsTo
    {
        return $this->belongsTo(QaCommittee::class, 'committee_id');
    }

    /** @return HasMany<QaMeetingItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(QaMeetingItem::class, 'meeting_id');
    }
}
