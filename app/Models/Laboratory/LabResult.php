<?php

namespace App\Models\Laboratory;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabResult extends Model
{
    protected $fillable = [
        'company_id', 'branch_id', 'lab_order_item_id', 'test_id', 'specimen_id',
        'result_type', 'numeric_value', 'text_value', 'qualitative_value', 'unit',
        'reference_range_low', 'reference_range_high', 'reference_range_text',
        'abnormal_flag', 'critical_flag', 'result_status',
        'entered_by', 'entered_at', 'technical_validated_by', 'technical_validated_at',
        'pathologist_approved_by', 'pathologist_approved_at', 'reported_at',
        'version', 'is_current', 'amended_from_id', 'amendment_reason', 'amended_by',
    ];

    protected function casts(): array
    {
        return [
            'numeric_value' => 'decimal:4',
            'reference_range_low' => 'decimal:4',
            'reference_range_high' => 'decimal:4',
            'critical_flag' => 'boolean',
            'is_current' => 'boolean',
            'version' => 'integer',
            'entered_at' => 'datetime',
            'technical_validated_at' => 'datetime',
            'pathologist_approved_at' => 'datetime',
            'reported_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo { return $this->belongsTo(\App\Models\Company::class); }
    public function branch(): BelongsTo { return $this->belongsTo(\App\Models\Branch::class); }
    public function orderItem(): BelongsTo { return $this->belongsTo(LabOrderItem::class, 'lab_order_item_id'); }
    public function test(): BelongsTo { return $this->belongsTo(LabTest::class, 'test_id'); }
    public function specimen(): BelongsTo { return $this->belongsTo(LabSpecimen::class, 'specimen_id'); }
    public function enteredBy(): BelongsTo { return $this->belongsTo(User::class, 'entered_by'); }
    public function technicalValidatedBy(): BelongsTo { return $this->belongsTo(User::class, 'technical_validated_by'); }
    public function pathologistApprovedBy(): BelongsTo { return $this->belongsTo(User::class, 'pathologist_approved_by'); }
    public function amendedBy(): BelongsTo { return $this->belongsTo(User::class, 'amended_by'); }
    public function amendedFrom(): BelongsTo { return $this->belongsTo(LabResult::class, 'amended_from_id'); }
    public function amendments(): HasMany { return $this->hasMany(LabResult::class, 'amended_from_id'); }
    public function criticalAlerts(): HasMany { return $this->hasMany(LabCriticalResultAlert::class, 'result_id'); }

    public function isReported(): bool { return $this->result_status === 'reported'; }
    public function isTechnicallyValidated(): bool { return in_array($this->result_status, ['technically_validated', 'pathologist_validated', 'reported'], true); }
    public function isPathologistValidated(): bool { return in_array($this->result_status, ['pathologist_validated', 'reported'], true); }

    /**
     * Full version chain, oldest first, by walking amended_from_id back to the root
     * and then reading forward via amendments() — used for the result-history view.
     */
    public function versions()
    {
        $root = $this;

        while ($root->amended_from_id) {
            $root = $root->amendedFrom;
        }

        $chain = collect([$root]);
        $current = $root;

        while ($next = $current->amendments()->first()) {
            $chain->push($next);
            $current = $next;
        }

        return $chain;
    }

    public function scopeForTenant($query, int $companyId, ?int $branchId = null)
    {
        return $query->where('company_id', $companyId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
    }
}
