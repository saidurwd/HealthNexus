<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PatientHistory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'patient_id',
        'condition',
        'description',
        'diagnosed_at',
        'resolved_at',
        'is_active',
        'recorded_by',
    ];

    protected $casts = [
        'diagnosed_at' => 'date',
        'resolved_at' => 'date',
        'is_active' => 'boolean',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
