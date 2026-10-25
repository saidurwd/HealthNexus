<?php

namespace App\Models\Laboratory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LabSpecimenType extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id', 'branch_id', 'code', 'name', 'description',
        'collection_requirements', 'storage_temperature', 'stability_hours',
        'max_processing_minutes', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'stability_hours' => 'integer', 'max_processing_minutes' => 'integer'];
    }

    public function organization(): BelongsTo { return $this->belongsTo(\App\Models\Organization::class); }
    public function branch(): BelongsTo { return $this->belongsTo(\App\Models\Branch::class); }
    public function tests(): HasMany { return $this->hasMany(LabTest::class, 'specimen_type_id'); }

    public function scopeForTenant($query, int $organizationId, ?int $branchId = null)
    {
        return $query->where('organization_id', $organizationId)->where(fn ($q) => $q->where('branch_id', $branchId)->orWhereNull('branch_id'));
    }
}
