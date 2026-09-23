<?php

namespace App\Models\Radiology;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RadiologyDicomEvent extends Model
{
    protected $fillable = [
        'company_id', 'branch_id', 'radiology_examination_id', 'pacs_server_id',
        'event_type', 'status', 'summary', 'error',
    ];

    public function company(): BelongsTo { return $this->belongsTo(\App\Models\Company::class); }
    public function branch(): BelongsTo { return $this->belongsTo(\App\Models\Branch::class); }
    public function examination(): BelongsTo { return $this->belongsTo(RadiologyExamination::class, 'radiology_examination_id'); }
    public function pacsServer(): BelongsTo { return $this->belongsTo(RadiologyPacsServer::class, 'pacs_server_id'); }

    public function scopeForTenant($query, int $companyId, ?int $branchId = null)
    {
        return $query->where('company_id', $companyId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
    }
}
