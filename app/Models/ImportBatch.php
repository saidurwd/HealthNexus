<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportBatch extends Model
{
    use HasPublicId;

    protected $guarded = ['id', 'public_id'];

    protected $casts = [
        'header' => 'array', 'options' => 'array', 'validated_at' => 'datetime', 'committed_at' => 'datetime', 'rolled_back_at' => 'datetime',
    ];

    public function rows(): HasMany
    {
        return $this->hasMany(ImportRow::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
