<?php

namespace App\Models\Ai;

use App\Models\Concerns\HasPublicId;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiOutput extends Model
{
    use HasPublicId;

    protected $table = 'ai_outputs';

    protected $guarded = ['id'];

    protected $casts = ['lines' => 'array', 'sources' => 'array', 'expires_at' => 'datetime', 'reviewed_at' => 'datetime'];

    /** @return BelongsTo<AiRequest, $this> */
    public function request(): BelongsTo
    {
        return $this->belongsTo(AiRequest::class, 'request_id');
    }

    /** @return BelongsTo<User, $this> */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /** @return HasMany<AiFeedback, $this> */
    public function feedback(): HasMany
    {
        return $this->hasMany(AiFeedback::class, 'output_id');
    }
}
