<?php

namespace App\Models\Quality;

use App\Models\Concerns\HasPublicId;
use App\Models\User;
use Database\Factories\Quality\QaActionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QaAction extends Model
{
    use HasFactory, HasPublicId;

    protected static function newFactory(): QaActionFactory
    {
        return QaActionFactory::new();
    }

    protected $table = 'qa_actions';

    protected $guarded = ['id'];

    protected $casts = ['due_on' => 'date:Y-m-d', 'done_at' => 'datetime', 'verified_at' => 'datetime', 'effective' => 'boolean', 'overdue_alerted_at' => 'datetime'];

    /** @return BelongsTo<User, $this> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /** @return BelongsTo<User, $this> */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
