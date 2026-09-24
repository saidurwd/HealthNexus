<?php

namespace App\Models\Ipd;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class IpdBed extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_AVAILABLE = 'available';
    public const STATUS_RESERVED = 'reserved';
    public const STATUS_OCCUPIED = 'occupied';
    public const STATUS_CLEANING = 'cleaning';
    public const STATUS_BLOCKED = 'blocked';
    public const STATUS_MAINTENANCE = 'maintenance';
    public const STATUS_ISOLATION = 'isolation';
    public const STATUS_OUT_OF_SERVICE = 'out_of_service';
    public const STATUS_PENDING_TRANSFER = 'pending_transfer';
    public const STATUS_PENDING_DISCHARGE = 'pending_discharge';

    protected $fillable = [
        'company_id', 'branch_id', 'room_id', 'bed_type_id', 'bed_code', 'bed_name',
        'gender_type', 'status', 'isolation_capable', 'icu_capable', 'ventilator_capable',
        'oxygen_available', 'monitor_available', 'is_vip', 'is_pediatric', 'is_maternity',
        'is_bariatric', 'is_accessible', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'isolation_capable' => 'boolean',
            'icu_capable' => 'boolean',
            'ventilator_capable' => 'boolean',
            'oxygen_available' => 'boolean',
            'monitor_available' => 'boolean',
            'is_vip' => 'boolean',
            'is_pediatric' => 'boolean',
            'is_maternity' => 'boolean',
            'is_bariatric' => 'boolean',
            'is_accessible' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo { return $this->belongsTo(\App\Models\Company::class); }
    public function branch(): BelongsTo { return $this->belongsTo(\App\Models\Branch::class); }
    public function room(): BelongsTo { return $this->belongsTo(IpdRoom::class, 'room_id'); }
    public function bedType(): BelongsTo { return $this->belongsTo(IpdBedType::class, 'bed_type_id'); }
    public function allocations(): HasMany { return $this->hasMany(IpdBedAllocation::class, 'bed_id'); }
    public function reservations(): HasMany { return $this->hasMany(IpdBedReservation::class, 'bed_id'); }
    public function blocks(): HasMany { return $this->hasMany(IpdBedBlock::class, 'bed_id'); }

    public function currentAllocation(): HasOne
    {
        return $this->hasOne(IpdBedAllocation::class, 'bed_id')->whereNull('released_at')->latestOfMany();
    }

    public function scopeForTenant($query, int $companyId, ?int $branchId = null)
    {
        return $query->where('ipd_beds.company_id', $companyId)
            ->where(fn ($q) => $q->where('ipd_beds.branch_id', $branchId)->orWhereNull('ipd_beds.branch_id'));
    }
}
