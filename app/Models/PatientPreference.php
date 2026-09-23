<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientPreference extends Model
{
    protected $fillable = [
        'company_id',
        'patient_id',
        'preferred_language',
        'preferred_contact_method',
        'preferred_notification_channel',
        'accessibility_requirements',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
