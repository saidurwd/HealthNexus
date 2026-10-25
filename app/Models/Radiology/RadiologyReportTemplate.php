<?php

namespace App\Models\Radiology;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RadiologyReportTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['organization_id', 'branch_id', 'procedure_id', 'modality_type', 'code', 'name', 'sections', 'is_active'];

    protected function casts(): array
    {
        return ['sections' => 'array', 'is_active' => 'boolean'];
    }

    public function organization(): BelongsTo { return $this->belongsTo(\App\Models\Organization::class); }
    public function branch(): BelongsTo { return $this->belongsTo(\App\Models\Branch::class); }
    public function procedure(): BelongsTo { return $this->belongsTo(RadiologyProcedure::class, 'procedure_id'); }

    public function scopeForTenant($query, int $organizationId, ?int $branchId = null)
    {
        return $query->where('organization_id', $organizationId)->where(fn ($q) => $q->where('branch_id', $branchId)->orWhereNull('branch_id'));
    }
}
