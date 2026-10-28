<?php

namespace App\Models\Portal;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortalMessage extends Model
{
    protected $table = 'portal_messages';

    protected $guarded = ['id'];

    public $timestamps = false;

    protected $casts = ['read_at' => 'datetime', 'created_at' => 'datetime'];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
