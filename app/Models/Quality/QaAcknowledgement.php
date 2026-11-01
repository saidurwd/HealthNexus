<?php

namespace App\Models\Quality;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QaAcknowledgement extends Model
{
    protected $table = 'qa_acknowledgements';

    protected $guarded = ['id'];

    public $timestamps = false;

    protected $casts = ['acknowledged_at' => 'datetime'];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
