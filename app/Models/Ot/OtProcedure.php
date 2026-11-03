<?php

namespace App\Models\Ot;

use App\Models\Specialty;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OtProcedure extends Model
{
    protected $fillable = [
        'organization_id', 'branch_id', 'category_id', 'specialty_id', 'code', 'name', 'description',
        'default_duration_minutes', 'anesthesia_category', 'laterality_applicable', 'site_applicable',
        'day_care_eligible', 'emergency_eligible', 'requires_implant', 'requires_blood',
        'requires_specimen', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'laterality_applicable' => 'boolean',
            'site_applicable' => 'boolean',
            'day_care_eligible' => 'boolean',
            'emergency_eligible' => 'boolean',
            'requires_implant' => 'boolean',
            'requires_blood' => 'boolean',
            'requires_specimen' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(OtProcedureCategory::class, 'category_id');
    }

    public function specialty(): BelongsTo
    {
        return $this->belongsTo(Specialty::class);
    }

    public function scopeForTenant($query, int $organizationId, ?int $branchId = null)
    {
        return $query->where('organization_id', $organizationId)
            ->where(fn ($q) => $q->where('branch_id', $branchId)->orWhereNull('branch_id'));
    }
}
