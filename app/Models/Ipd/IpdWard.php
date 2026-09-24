<?php

namespace App\Models\Ipd;

use App\Models\Department;
use App\Models\Specialty;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class IpdWard extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id', 'branch_id', 'building_id', 'floor_id', 'department_id', 'specialty_id',
        'code', 'name', 'gender_policy', 'capacity', 'isolation_capable', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'isolation_capable' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo { return $this->belongsTo(\App\Models\Company::class); }
    public function branch(): BelongsTo { return $this->belongsTo(\App\Models\Branch::class); }
    public function building(): BelongsTo { return $this->belongsTo(IpdBuilding::class, 'building_id'); }
    public function floor(): BelongsTo { return $this->belongsTo(IpdFloor::class, 'floor_id'); }
    public function department(): BelongsTo { return $this->belongsTo(Department::class); }
    public function specialty(): BelongsTo { return $this->belongsTo(Specialty::class); }
    public function rooms(): HasMany { return $this->hasMany(IpdRoom::class, 'ward_id'); }

    public function scopeForTenant($query, int $companyId, ?int $branchId = null)
    {
        return $query->where('company_id', $companyId)->where(fn ($q) => $q->where('branch_id', $branchId)->orWhereNull('branch_id'));
    }
}
