<?php

namespace App\Models\Nursing;

use App\Models\Ipd\IpdAdmission;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NursingIntakeOutputRecord extends Model
{
    public const TYPE_INTAKE = 'intake';

    public const TYPE_OUTPUT = 'output';

    protected $fillable = [
        'organization_id', 'branch_id', 'episode_id', 'admission_id', 'patient_id',
        'type', 'category', 'amount', 'unit', 'route', 'recorded_at', 'recorded_by', 'source', 'notes',
    ];

    protected function casts(): array
    {
        return ['amount' => 'float', 'recorded_at' => 'datetime'];
    }

    public function episode(): BelongsTo
    {
        return $this->belongsTo(NursingEpisode::class, 'episode_id');
    }

    public function admission(): BelongsTo
    {
        return $this->belongsTo(IpdAdmission::class, 'admission_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function scopeForTenant($query, int $organizationId, ?int $branchId = null)
    {
        return $query->where('organization_id', $organizationId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
    }
}
