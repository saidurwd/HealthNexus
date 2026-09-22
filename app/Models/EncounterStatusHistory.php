<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EncounterStatusHistory extends Model
{
    protected $fillable = [
        'encounter_id',
        'from_status',
        'to_status',
        'changed_by',
        'changed_at',
        'reason',
        'metadata',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function encounter(): BelongsTo
    {
        return $this->belongsTo(Encounter::class);
    }

    public function changer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
