<?php

namespace App\Models\Radiology;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RadiologyPacsServer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id', 'branch_id', 'code', 'name', 'adapter_type', 'base_url', 'ae_title',
        'port', 'username', 'password', 'is_active', 'is_default',
    ];

    protected function casts(): array
    {
        return [
            'port' => 'integer',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'username' => 'encrypted',
            'password' => 'encrypted',
        ];
    }

    public function organization(): BelongsTo { return $this->belongsTo(\App\Models\Organization::class); }
    public function branch(): BelongsTo { return $this->belongsTo(\App\Models\Branch::class); }

    public function scopeForTenant($query, int $organizationId, ?int $branchId = null)
    {
        return $query->where('organization_id', $organizationId)->where(fn ($q) => $q->where('branch_id', $branchId)->orWhereNull('branch_id'));
    }
}
