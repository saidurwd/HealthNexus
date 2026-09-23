<?php

namespace App\Models\Laboratory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LabQcMaterial extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['company_id', 'branch_id', 'section_id', 'code', 'name', 'level', 'lot_number', 'expiry_date', 'is_active'];

    protected function casts(): array
    {
        return ['expiry_date' => 'date', 'is_active' => 'boolean'];
    }

    public function company(): BelongsTo { return $this->belongsTo(\App\Models\Company::class); }
    public function branch(): BelongsTo { return $this->belongsTo(\App\Models\Branch::class); }
    public function section(): BelongsTo { return $this->belongsTo(LabSection::class, 'section_id'); }
    public function runs(): HasMany { return $this->hasMany(LabQcRun::class, 'qc_material_id'); }

    public function scopeForTenant($query, int $companyId, ?int $branchId = null)
    {
        return $query->where('company_id', $companyId)->where(fn ($q) => $q->where('branch_id', $branchId)->orWhereNull('branch_id'));
    }
}
