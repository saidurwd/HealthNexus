<?php

namespace App\Models\Ed;

use App\Models\Concerns\HasPublicId;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EdProtocolStep extends Model
{
    use HasPublicId;

    protected $guarded = ['id', 'public_id'];

    protected $casts = ['due_at' => 'datetime', 'done_at' => 'datetime'];

    /** @return BelongsTo<EdProtocolRun, $this> */
    public function run(): BelongsTo
    {
        return $this->belongsTo(EdProtocolRun::class, 'run_id');
    }

    /** @return BelongsTo<User, $this> */
    public function doneBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'done_by');
    }

    public function isOverdue(): bool
    {
        return $this->done_at === null && $this->due_at !== null && $this->due_at->lte(now());
    }
}
