<?php

namespace App\Models\Icu;

use App\Models\PatientConsent;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IcuProcedureRecord extends Model
{
    protected $fillable = [
        'company_id', 'branch_id', 'episode_id', 'procedure_id', 'indication', 'findings',
        'complications', 'parameters', 'consent_id', 'performed_at', 'performed_by',
    ];

    protected function casts(): array
    {
        return ['parameters' => 'array', 'performed_at' => 'datetime'];
    }

    public function episode(): BelongsTo { return $this->belongsTo(IcuEpisode::class, 'episode_id'); }
    public function procedure(): BelongsTo { return $this->belongsTo(IcuProcedure::class, 'procedure_id'); }
    public function consent(): BelongsTo { return $this->belongsTo(PatientConsent::class, 'consent_id'); }
    public function performedBy(): BelongsTo { return $this->belongsTo(User::class, 'performed_by'); }

    public function scopeForTenant($query, int $companyId, ?int $branchId = null)
    {
        return $query->where('company_id', $companyId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
    }
}
