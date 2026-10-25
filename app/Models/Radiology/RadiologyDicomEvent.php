<?php

namespace App\Models\Radiology;

use App\Models\Branch;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RadiologyDicomEvent extends Model
{
    protected $fillable = [
        'organization_id', 'branch_id', 'radiology_examination_id', 'pacs_server_id',
        'event_type', 'status', 'summary', 'error',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function examination(): BelongsTo
    {
        return $this->belongsTo(RadiologyExamination::class, 'radiology_examination_id');
    }

    public function pacsServer(): BelongsTo
    {
        return $this->belongsTo(RadiologyPacsServer::class, 'pacs_server_id');
    }

    public function scopeForTenant($query, int $organizationId, ?int $branchId = null)
    {
        return $query->where('organization_id', $organizationId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
    }
}
