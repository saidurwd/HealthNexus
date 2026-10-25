<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PatientContact extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'patient_id',
        'name',
        'relationship',
        'phone',
        'email',
        'address',
        'is_emergency',
    ];

    protected $casts = [
        'is_emergency' => 'boolean',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
