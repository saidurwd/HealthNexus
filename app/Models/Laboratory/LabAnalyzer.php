<?php

namespace App\Models\Laboratory;

use App\Models\Branch;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LabAnalyzer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['organization_id', 'branch_id', 'section_id', 'code', 'name', 'vendor', 'connection_type', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(LabSection::class, 'section_id');
    }

    public function analyzerTests(): HasMany
    {
        return $this->hasMany(LabAnalyzerTest::class, 'analyzer_id');
    }

    public function qcRuns(): HasMany
    {
        return $this->hasMany(LabQcRun::class, 'analyzer_id');
    }

    public function scopeForTenant($query, int $organizationId, ?int $branchId = null)
    {
        return $query->where('organization_id', $organizationId)->where(fn ($q) => $q->where('branch_id', $branchId)->orWhereNull('branch_id'));
    }
}
