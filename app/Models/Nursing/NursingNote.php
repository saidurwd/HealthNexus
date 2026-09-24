<?php

namespace App\Models\Nursing;

use App\Models\Ipd\IpdAdmission;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NursingNote extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_FINAL = 'final';

    protected $fillable = [
        'company_id', 'branch_id', 'episode_id', 'admission_id', 'patient_id', 'note_type',
        'content', 'status', 'created_by', 'signed_at', 'signed_by',
    ];

    protected function casts(): array
    {
        return ['signed_at' => 'datetime'];
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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function signedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signed_by');
    }

    public function amendments(): HasMany
    {
        return $this->hasMany(NursingNoteAmendment::class, 'note_id');
    }

    public function scopeForTenant($query, int $companyId, ?int $branchId = null)
    {
        return $query->where('company_id', $companyId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
    }
}
