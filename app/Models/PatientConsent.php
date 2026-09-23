<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientConsent extends Model
{
    protected $fillable = [
        'company_id',
        'patient_id',
        'consent_type',
        'version',
        'status',
        'notes',
        'document_file_id',
        'granted_by',
        'granted_at',
        'withdrawn_by',
        'withdrawn_at',
    ];

    protected $casts = [
        'version' => 'integer',
        'granted_at' => 'datetime',
        'withdrawn_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(File::class, 'document_file_id');
    }

    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    public function withdrawnBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'withdrawn_by');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
