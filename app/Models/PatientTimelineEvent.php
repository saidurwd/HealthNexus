<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PatientTimelineEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'company_id',
        'patient_id',
        'event_type',
        'description',
        'subject_type',
        'subject_id',
        'actor_id',
        'metadata',
        'event_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'event_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
