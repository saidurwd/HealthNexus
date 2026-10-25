<?php

namespace App\Models\Ipd;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IpdBedReservation extends Model
{
    use HasFactory;

    public const STATUS_RESERVED = 'reserved';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_CONVERTED = 'converted';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'organization_id', 'branch_id', 'patient_id', 'admission_request_id', 'bed_id',
        'reserved_at', 'expires_at', 'status', 'reason', 'requested_by',
    ];

    protected function casts(): array
    {
        return ['reserved_at' => 'datetime', 'expires_at' => 'datetime'];
    }

    public function organization(): BelongsTo { return $this->belongsTo(\App\Models\Organization::class); }
    public function branch(): BelongsTo { return $this->belongsTo(\App\Models\Branch::class); }
    public function patient(): BelongsTo { return $this->belongsTo(Patient::class); }
    public function admissionRequest(): BelongsTo { return $this->belongsTo(IpdAdmissionRequest::class, 'admission_request_id'); }
    public function bed(): BelongsTo { return $this->belongsTo(IpdBed::class, 'bed_id'); }
    public function requestedBy(): BelongsTo { return $this->belongsTo(User::class, 'requested_by'); }

    public function scopeForTenant($query, int $organizationId, ?int $branchId = null)
    {
        return $query->where('organization_id', $organizationId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
    }
}
