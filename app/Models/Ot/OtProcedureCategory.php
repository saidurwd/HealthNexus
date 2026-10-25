<?php

namespace App\Models\Ot;

use Illuminate\Database\Eloquent\Model;

class OtProcedureCategory extends Model
{
    protected $fillable = ['organization_id', 'branch_id', 'code', 'name', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function scopeForTenant($query, int $organizationId, ?int $branchId = null)
    {
        return $query->where('organization_id', $organizationId)
            ->where(fn ($q) => $q->where('branch_id', $branchId)->orWhereNull('branch_id'));
    }
}
