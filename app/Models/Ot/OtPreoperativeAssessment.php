<?php

namespace App\Models\Ot;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OtPreoperativeAssessment extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_FINAL = 'final';

    protected $fillable = [
        'organization_id', 'branch_id', 'surgery_id', 'patient_id', 'sections', 'status',
        'assessed_by', 'finalized_at', 'finalized_by',
    ];

    protected function casts(): array
    {
        return ['sections' => 'array', 'finalized_at' => 'datetime'];
    }

    public function surgery(): BelongsTo
    {
        return $this->belongsTo(OtSurgery::class, 'surgery_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function assessedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessed_by');
    }

    public function finalizedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }
}
