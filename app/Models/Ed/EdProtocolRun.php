<?php

namespace App\Models\Ed;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EdProtocolRun extends Model
{
    use HasPublicId;

    public const ACTIVE = 'active';

    public const COMPLETED = 'completed';

    public const CANCELLED = 'cancelled';

    protected $guarded = ['id', 'public_id'];

    protected $casts = ['started_at' => 'datetime', 'ended_at' => 'datetime'];

    /** @return BelongsTo<EdVisit, $this> */
    public function visit(): BelongsTo
    {
        return $this->belongsTo(EdVisit::class, 'visit_id');
    }

    /** @return HasMany<EdProtocolStep, $this> */
    public function steps(): HasMany
    {
        return $this->hasMany(EdProtocolStep::class, 'run_id')->orderBy('id');
    }
}
