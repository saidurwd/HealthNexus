<?php

namespace App\Models\Radiology;

use App\Models\ClinicalOrder;
use App\Models\Department;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RadiologyOrder extends Model
{
    use HasFactory, SoftDeletes;

    public const ACTIVE_STATUSES = [
        'ordered', 'registered', 'scheduled', 'checked_in', 'preparing', 'ready',
        'in_progress', 'completed', 'images_available', 'reporting',
    ];

    protected $fillable = [
        'company_id', 'branch_id', 'department_id', 'patient_id', 'encounter_id', 'clinical_order_id',
        'order_number', 'accession_number', 'priority', 'status', 'ordered_by', 'ordered_at',
        'registered_by', 'registered_at', 'clinical_indication', 'provisional_diagnosis',
        'relevant_history', 'requested_date', 'contrast_required', 'special_instructions',
        'cancelled_by', 'cancelled_at', 'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'ordered_at' => 'datetime',
            'registered_at' => 'datetime',
            'requested_date' => 'date',
            'contrast_required' => 'boolean',
            'cancelled_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo { return $this->belongsTo(\App\Models\Company::class); }
    public function branch(): BelongsTo { return $this->belongsTo(\App\Models\Branch::class); }
    public function department(): BelongsTo { return $this->belongsTo(Department::class); }
    public function patient(): BelongsTo { return $this->belongsTo(Patient::class); }
    public function encounter(): BelongsTo { return $this->belongsTo(Encounter::class); }
    public function clinicalOrder(): BelongsTo { return $this->belongsTo(ClinicalOrder::class, 'clinical_order_id'); }
    public function orderedBy(): BelongsTo { return $this->belongsTo(User::class, 'ordered_by'); }
    public function registeredBy(): BelongsTo { return $this->belongsTo(User::class, 'registered_by'); }
    public function cancelledBy(): BelongsTo { return $this->belongsTo(User::class, 'cancelled_by'); }
    public function items(): HasMany { return $this->hasMany(RadiologyOrderItem::class, 'radiology_order_id'); }

    public function isActive(): bool { return in_array($this->status, self::ACTIVE_STATUSES, true); }
    public function isCancelled(): bool { return $this->status === 'cancelled'; }
    public function isReported(): bool { return $this->status === 'reported'; }

    public function scopeForTenant($query, int $companyId, ?int $branchId = null)
    {
        return $query->where('company_id', $companyId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
    }
}
