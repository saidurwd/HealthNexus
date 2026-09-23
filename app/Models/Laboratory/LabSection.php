<?php

namespace App\Models\Laboratory;

use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LabSection extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['company_id', 'branch_id', 'department_id', 'code', 'name', 'description', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function company(): BelongsTo { return $this->belongsTo(\App\Models\Company::class); }
    public function branch(): BelongsTo { return $this->belongsTo(\App\Models\Branch::class); }
    public function department(): BelongsTo { return $this->belongsTo(Department::class); }
    public function tests(): HasMany { return $this->hasMany(LabTest::class, 'section_id'); }
    public function analyzers(): HasMany { return $this->hasMany(LabAnalyzer::class, 'section_id'); }

    public function scopeForTenant($query, int $companyId, ?int $branchId = null)
    {
        return $query->where('company_id', $companyId)->where(fn ($q) => $q->where('branch_id', $branchId)->orWhereNull('branch_id'));
    }
}
