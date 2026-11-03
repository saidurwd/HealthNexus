<?php

namespace App\Models\Laboratory;

use App\Models\Branch;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabQcRun extends Model
{
    protected $fillable = [
        'organization_id', 'branch_id', 'qc_material_id', 'test_id', 'analyzer_id', 'run_at',
        'expected_low', 'expected_high', 'observed_value', 'status', 'performed_by', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'run_at' => 'datetime',
            'expected_low' => 'decimal:4',
            'expected_high' => 'decimal:4',
            'observed_value' => 'decimal:4',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function qcMaterial(): BelongsTo
    {
        return $this->belongsTo(LabQcMaterial::class, 'qc_material_id');
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(LabTest::class, 'test_id');
    }

    public function analyzer(): BelongsTo
    {
        return $this->belongsTo(LabAnalyzer::class, 'analyzer_id');
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function isPass(): bool
    {
        return $this->status === 'pass';
    }

    public function isFail(): bool
    {
        return $this->status === 'fail';
    }

    public function scopeForTenant($query, int $organizationId, ?int $branchId = null)
    {
        return $query->where('organization_id', $organizationId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
    }
}
