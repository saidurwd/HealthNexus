<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientPortalAccount extends Model
{
    protected $fillable = [
        'company_id',
        'patient_id',
        'user_id',
        'email',
        'is_active',
        'invited_at',
        'activated_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'invited_at' => 'datetime',
        'activated_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
