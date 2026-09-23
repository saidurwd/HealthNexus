<?php

namespace App\Models\Laboratory;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabSpecimen extends Model
{
    protected $fillable = [
        'company_id', 'branch_id', 'lab_order_id', 'specimen_type_id', 'container_type_id',
        'accession_number', 'barcode', 'status', 'collected_by', 'collected_at',
        'received_by', 'received_at', 'rejected_by', 'rejected_at', 'rejection_reason',
        'storage_location', 'notes',
    ];

    protected function casts(): array
    {
        return ['collected_at' => 'datetime', 'received_at' => 'datetime', 'rejected_at' => 'datetime'];
    }

    public function company(): BelongsTo { return $this->belongsTo(\App\Models\Company::class); }
    public function branch(): BelongsTo { return $this->belongsTo(\App\Models\Branch::class); }
    public function labOrder(): BelongsTo { return $this->belongsTo(LabOrder::class, 'lab_order_id'); }
    public function specimenType(): BelongsTo { return $this->belongsTo(LabSpecimenType::class, 'specimen_type_id'); }
    public function containerType(): BelongsTo { return $this->belongsTo(LabContainerType::class, 'container_type_id'); }
    public function collectedBy(): BelongsTo { return $this->belongsTo(User::class, 'collected_by'); }
    public function receivedBy(): BelongsTo { return $this->belongsTo(User::class, 'received_by'); }
    public function rejectedBy(): BelongsTo { return $this->belongsTo(User::class, 'rejected_by'); }
    public function orderItems(): HasMany { return $this->hasMany(LabOrderItem::class, 'specimen_id'); }
    public function results(): HasMany { return $this->hasMany(LabResult::class, 'specimen_id'); }

    public function isRejected(): bool { return $this->status === 'rejected'; }
    public function isReceived(): bool { return in_array($this->status, ['received', 'accepted', 'processing', 'completed'], true); }

    public function scopeForTenant($query, int $companyId, ?int $branchId = null)
    {
        return $query->where('company_id', $companyId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
    }
}
