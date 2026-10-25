<?php

namespace App\Models\Icu;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IcuDevice extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_REMOVED = 'removed';

    protected $fillable = [
        'company_id', 'branch_id', 'episode_id', 'patient_id', 'device_type', 'insertion_date',
        'site', 'side', 'inserted_by', 'indication', 'status', 'care_schedule', 'last_assessment_at',
        'removal_date', 'removal_by', 'complication',
    ];

    protected function casts(): array
    {
        return ['insertion_date' => 'date', 'last_assessment_at' => 'datetime', 'removal_date' => 'date'];
    }

    public function episode(): BelongsTo { return $this->belongsTo(IcuEpisode::class, 'episode_id'); }
    public function patient(): BelongsTo { return $this->belongsTo(Patient::class); }
    public function insertedBy(): BelongsTo { return $this->belongsTo(User::class, 'inserted_by'); }
    public function removalBy(): BelongsTo { return $this->belongsTo(User::class, 'removal_by'); }
    public function assessments(): HasMany { return $this->hasMany(IcuDeviceAssessment::class, 'device_id'); }

    public function scopeForTenant($query, int $companyId, ?int $branchId = null)
    {
        return $query->where('company_id', $companyId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
    }
}
