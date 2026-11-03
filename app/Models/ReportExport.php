<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportExport extends Model
{
    use HasPublicId;

    protected $guarded = ['id', 'public_id'];

    protected $casts = [
        'filters' => 'array', 'requested_at' => 'datetime', 'completed_at' => 'datetime',
        'expires_at' => 'datetime', 'last_downloaded_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isDownloadable(): bool
    {
        return $this->status === 'completed' && $this->expires_at?->isFuture() === true;
    }
}
