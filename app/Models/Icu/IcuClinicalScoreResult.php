<?php

namespace App\Models\Icu;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IcuClinicalScoreResult extends Model
{
    protected $fillable = [
        'company_id', 'branch_id', 'episode_id', 'score_code', 'score_version', 'input_values',
        'result_value', 'interpretation', 'calculated_by', 'calculated_at',
    ];

    protected function casts(): array
    {
        return ['input_values' => 'array', 'result_value' => 'float', 'calculated_at' => 'datetime'];
    }

    public function episode(): BelongsTo { return $this->belongsTo(IcuEpisode::class, 'episode_id'); }
    public function calculatedBy(): BelongsTo { return $this->belongsTo(User::class, 'calculated_by'); }

    public function scopeForTenant($query, int $companyId, ?int $branchId = null)
    {
        return $query->where('company_id', $companyId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
    }
}
