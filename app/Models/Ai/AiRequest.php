<?php

namespace App\Models\Ai;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AiRequest extends Model
{
    use HasPublicId;

    protected $table = 'ai_requests';

    protected $guarded = ['id'];

    public const UPDATED_AT = null;

    protected $casts = ['input' => 'array'];

    /** @return HasOne<AiOutput, $this> */
    public function output(): HasOne
    {
        return $this->hasOne(AiOutput::class, 'request_id');
    }
}
