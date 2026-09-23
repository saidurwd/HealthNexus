<?php

namespace App\Models\Pharmacy;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PharmacyQuarantine extends Model
{
    use HasFactory;

    public const STATUS_QUARANTINED = 'quarantined';
    public const STATUS_RELEASED = 'released';
    public const STATUS_DISPOSED = 'disposed';

    protected $table = 'pharmacy_quarantine';

    protected $fillable = [
        'company_id', 'branch_id', 'store_id', 'medication_id', 'batch_id', 'quantity',
        'reason_type', 'reason', 'status', 'quarantined_by', 'quarantined_at',
        'released_by', 'released_at', 'disposed_by', 'disposed_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'quarantined_at' => 'datetime',
            'released_at' => 'datetime',
            'disposed_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo { return $this->belongsTo(\App\Models\Company::class); }
    public function branch(): BelongsTo { return $this->belongsTo(\App\Models\Branch::class); }
    public function store(): BelongsTo { return $this->belongsTo(PharmacyStore::class, 'store_id'); }
    public function medication(): BelongsTo { return $this->belongsTo(PharmacyMedication::class, 'medication_id'); }
    public function batch(): BelongsTo { return $this->belongsTo(PharmacyBatch::class, 'batch_id'); }
    public function quarantinedBy(): BelongsTo { return $this->belongsTo(User::class, 'quarantined_by'); }
    public function releasedBy(): BelongsTo { return $this->belongsTo(User::class, 'released_by'); }
    public function disposedBy(): BelongsTo { return $this->belongsTo(User::class, 'disposed_by'); }

    public function scopeForTenant($query, int $companyId, ?int $branchId = null)
    {
        return $query->where('company_id', $companyId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
    }
}
