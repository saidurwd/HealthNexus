<?php

namespace App\Models\Radiology;

use App\Models\Branch;
use App\Models\Organization;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RadiologyStudy extends Model
{
    protected $fillable = [
        'organization_id', 'branch_id', 'radiology_examination_id', 'patient_id', 'pacs_server_id',
        'accession_number', 'study_instance_uid', 'study_id', 'study_date', 'study_time',
        'modality', 'study_description', 'body_part', 'referring_physician', 'institution_name',
        'pacs_status', 'study_status', 'number_of_series', 'number_of_instances', 'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'study_date' => 'date',
            'number_of_series' => 'integer',
            'number_of_instances' => 'integer',
            'last_synced_at' => 'datetime',
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

    public function examination(): BelongsTo
    {
        return $this->belongsTo(RadiologyExamination::class, 'radiology_examination_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function pacsServer(): BelongsTo
    {
        return $this->belongsTo(RadiologyPacsServer::class, 'pacs_server_id');
    }

    public function series(): HasMany
    {
        return $this->hasMany(RadiologySeries::class, 'study_id');
    }

    public function isReconciled(): bool
    {
        return $this->study_status === 'reconciled';
    }

    public function hasImages(): bool
    {
        return $this->study_instance_uid !== null;
    }

    public function scopeForTenant($query, int $organizationId, ?int $branchId = null)
    {
        return $query->where('organization_id', $organizationId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
    }
}
