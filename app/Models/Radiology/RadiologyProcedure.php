<?php

namespace App\Models\Radiology;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RadiologyProcedure extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id', 'branch_id', 'section_id', 'body_part_id', 'code', 'name', 'modality_type',
        'description', 'duration_minutes', 'turnaround_time_minutes', 'contrast_required',
        'preparation_required', 'sedation_required', 'preparation_instructions',
        'requires_senior_approval', 'is_active', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'contrast_required' => 'boolean',
            'preparation_required' => 'boolean',
            'sedation_required' => 'boolean',
            'requires_senior_approval' => 'boolean',
            'is_active' => 'boolean',
            'duration_minutes' => 'integer',
            'turnaround_time_minutes' => 'integer',
        ];
    }

    public function company(): BelongsTo { return $this->belongsTo(\App\Models\Company::class); }
    public function branch(): BelongsTo { return $this->belongsTo(\App\Models\Branch::class); }
    public function section(): BelongsTo { return $this->belongsTo(RadiologySection::class, 'section_id'); }
    public function bodyPart(): BelongsTo { return $this->belongsTo(RadiologyBodyPart::class, 'body_part_id'); }
    public function protocols(): HasMany { return $this->hasMany(RadiologyProtocol::class, 'procedure_id'); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function updatedBy(): BelongsTo { return $this->belongsTo(User::class, 'updated_by'); }

    public function scopeForTenant($query, int $companyId, ?int $branchId = null)
    {
        return $query->where('company_id', $companyId)->where(fn ($q) => $q->where('branch_id', $branchId)->orWhereNull('branch_id'));
    }
}
