<?php

namespace App\Models\Laboratory;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LabTest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id', 'branch_id', 'category_id', 'section_id', 'specimen_type_id', 'container_type_id',
        'code', 'name', 'short_name', 'description', 'test_type', 'method', 'unit',
        'fasting_required', 'turnaround_time_minutes', 'is_panel', 'requires_pathologist_approval',
        'is_active', 'effective_from', 'effective_to', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'fasting_required' => 'boolean',
            'is_panel' => 'boolean',
            'requires_pathologist_approval' => 'boolean',
            'is_active' => 'boolean',
            'turnaround_time_minutes' => 'integer',
            'effective_from' => 'date',
            'effective_to' => 'date',
        ];
    }

    public function company(): BelongsTo { return $this->belongsTo(\App\Models\Company::class); }
    public function branch(): BelongsTo { return $this->belongsTo(\App\Models\Branch::class); }
    public function category(): BelongsTo { return $this->belongsTo(LabTestCategory::class, 'category_id'); }
    public function section(): BelongsTo { return $this->belongsTo(LabSection::class, 'section_id'); }
    public function specimenType(): BelongsTo { return $this->belongsTo(LabSpecimenType::class, 'specimen_type_id'); }
    public function containerType(): BelongsTo { return $this->belongsTo(LabContainerType::class, 'container_type_id'); }
    public function referenceRanges(): HasMany { return $this->hasMany(LabReferenceRange::class, 'test_id'); }
    public function criticalValues(): HasMany { return $this->hasMany(LabCriticalValue::class, 'test_id'); }
    public function panelItems(): HasMany { return $this->hasMany(LabPanelItem::class, 'test_id'); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function updatedBy(): BelongsTo { return $this->belongsTo(User::class, 'updated_by'); }

    public function scopeForTenant($query, int $companyId, ?int $branchId = null)
    {
        return $query->where('company_id', $companyId)->where(fn ($q) => $q->where('branch_id', $branchId)->orWhereNull('branch_id'));
    }
}
