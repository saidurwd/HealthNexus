<?php

namespace App\Models\Radiology;

use App\Models\Provider;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RadiologyReport extends Model
{
    protected $fillable = [
        'company_id', 'branch_id', 'examination_id', 'template_id', 'report_number', 'status',
        'radiologist_id', 'clinical_indication', 'technique', 'findings', 'impression', 'recommendation',
        'submitted_at', 'reviewed_by', 'approved_by', 'approved_at',
        'version', 'is_current', 'amended_from_id', 'amendment_reason', 'amended_by',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'version' => 'integer',
            'is_current' => 'boolean',
        ];
    }

    public function company(): BelongsTo { return $this->belongsTo(\App\Models\Company::class); }
    public function branch(): BelongsTo { return $this->belongsTo(\App\Models\Branch::class); }
    public function examination(): BelongsTo { return $this->belongsTo(RadiologyExamination::class, 'examination_id'); }
    public function template(): BelongsTo { return $this->belongsTo(RadiologyReportTemplate::class, 'template_id'); }
    public function radiologist(): BelongsTo { return $this->belongsTo(Provider::class, 'radiologist_id'); }
    public function reviewedBy(): BelongsTo { return $this->belongsTo(Provider::class, 'reviewed_by'); }
    public function approvedBy(): BelongsTo { return $this->belongsTo(Provider::class, 'approved_by'); }
    public function amendedBy(): BelongsTo { return $this->belongsTo(User::class, 'amended_by'); }
    public function amendedFrom(): BelongsTo { return $this->belongsTo(RadiologyReport::class, 'amended_from_id'); }
    public function amendments(): HasMany { return $this->hasMany(RadiologyReport::class, 'amended_from_id'); }
    public function findings(): HasMany { return $this->hasMany(RadiologyReportFinding::class, 'report_id'); }
    public function criticalFindings(): HasMany { return $this->hasMany(RadiologyCriticalFinding::class, 'report_id'); }

    public function isDraft(): bool { return $this->status === 'draft'; }
    public function isFinal(): bool { return in_array($this->status, ['final', 'amended'], true); }
    public function isMutable(): bool { return in_array($this->status, ['draft'], true); }

    public function scopeForTenant($query, int $companyId, ?int $branchId = null)
    {
        return $query->where('company_id', $companyId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
    }
}
