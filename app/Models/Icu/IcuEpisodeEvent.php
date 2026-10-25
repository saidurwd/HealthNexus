<?php

namespace App\Models\Icu;

use App\Models\Ipd\IpdBed;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IcuEpisodeEvent extends Model
{
    public const TYPE_ADMITTED = 'admitted';

    public const TYPE_BED_CHANGED = 'bed_changed';

    public const TYPE_TRANSFER_REQUESTED = 'transfer_requested';

    public const TYPE_TRANSFER_COMPLETED = 'transfer_completed';

    public const TYPE_STEP_DOWN = 'step_down';

    public const TYPE_DISCHARGED = 'discharged';

    public const TYPE_DEATH = 'death';

    protected $fillable = [
        'organization_id', 'branch_id', 'episode_id', 'event_type', 'from_status', 'to_status',
        'from_bed_id', 'to_bed_id', 'destination', 'reason', 'notes', 'created_by',
    ];

    public function episode(): BelongsTo { return $this->belongsTo(IcuEpisode::class, 'episode_id'); }
    public function fromBed(): BelongsTo { return $this->belongsTo(IpdBed::class, 'from_bed_id'); }
    public function toBed(): BelongsTo { return $this->belongsTo(IpdBed::class, 'to_bed_id'); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
