<?php

namespace App\Models\Ed;

use App\Models\Concerns\HasPublicId;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EdTriageRecord extends Model
{
    use HasPublicId;

    protected $guarded = ['id', 'public_id'];

    protected $casts = ['is_retriage' => 'boolean', 'discriminators' => 'array', 'assessed_at' => 'datetime'];

    public function visit(): BelongsTo
    {
        return $this->belongsTo(EdVisit::class, 'visit_id');
    }

    public function assessor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessed_by');
    }
}
