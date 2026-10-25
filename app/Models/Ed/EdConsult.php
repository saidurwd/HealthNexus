<?php

namespace App\Models\Ed;

use App\Models\Concerns\HasPublicId;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EdConsult extends Model
{
    use HasPublicId;

    public const REQUESTED = 'requested';

    public const RESPONDED = 'responded';

    public const CANCELLED = 'cancelled';

    protected $guarded = ['id', 'public_id'];

    protected $casts = ['requested_at' => 'datetime', 'due_at' => 'datetime', 'responded_at' => 'datetime'];

    /** @return BelongsTo<EdVisit, $this> */
    public function visit(): BelongsTo
    {
        return $this->belongsTo(EdVisit::class, 'visit_id');
    }

    /** @return BelongsTo<User, $this> */
    public function responder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responded_by');
    }

    public function isOverdue(): bool
    {
        return $this->status === self::REQUESTED && $this->due_at->lte(now());
    }
}
