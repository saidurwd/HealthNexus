<?php

namespace App\Models\Pharmacy;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PharmacyRecall extends Model
{
    use HasFactory;

    public const STATUS_INITIATED = 'initiated';
    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'company_id', 'branch_id', 'medication_id', 'batch_id', 'recall_number', 'reason',
        'status', 'initiated_by', 'initiated_at', 'closed_by', 'closed_at',
    ];

    protected function casts(): array
    {
        return ['initiated_at' => 'datetime', 'closed_at' => 'datetime'];
    }

    public function company(): BelongsTo { return $this->belongsTo(\App\Models\Company::class); }
    public function branch(): BelongsTo { return $this->belongsTo(\App\Models\Branch::class); }
    public function medication(): BelongsTo { return $this->belongsTo(PharmacyMedication::class, 'medication_id'); }
    public function batch(): BelongsTo { return $this->belongsTo(PharmacyBatch::class, 'batch_id'); }
    public function initiatedBy(): BelongsTo { return $this->belongsTo(User::class, 'initiated_by'); }
    public function closedBy(): BelongsTo { return $this->belongsTo(User::class, 'closed_by'); }

    public function scopeForTenant($query, int $companyId, ?int $branchId = null)
    {
        return $query->where('company_id', $companyId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
    }
}
