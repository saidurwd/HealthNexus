<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientDuplicateCandidate extends Model
{
    protected $fillable = [
        'company_id',
        'patient_id_a',
        'patient_id_b',
        'score',
        'classification',
        'status',
        'match_reasons',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
    ];

    protected $casts = [
        'score' => 'integer',
        'match_reasons' => 'array',
        'reviewed_at' => 'datetime',
    ];

    public function patientA(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id_a');
    }

    public function patientB(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id_b');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
