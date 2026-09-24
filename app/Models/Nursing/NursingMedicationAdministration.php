<?php

namespace App\Models\Nursing;

use App\Models\Encounter;
use App\Models\Ipd\IpdAdmission;
use App\Models\Patient;
use App\Models\Pharmacy\PharmacyBatch;
use App\Models\Pharmacy\PharmacyDispensingItem;
use App\Models\Pharmacy\PharmacyMedication;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NursingMedicationAdministration extends Model
{
    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_DUE = 'due';

    public const STATUS_ADMINISTERED = 'administered';

    public const STATUS_HELD = 'held';

    public const STATUS_REFUSED = 'refused';

    public const STATUS_OMITTED = 'omitted';

    public const STATUS_MISSED = 'missed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_NOT_AVAILABLE = 'not_available';

    public const STATUS_CONTRAINDICATED = 'contraindicated';

    public const STATUS_DEFERRED = 'deferred';

    public const STATUS_NOT_APPLICABLE = 'not_applicable';

    public const TERMINAL_STATUSES = [
        self::STATUS_ADMINISTERED, self::STATUS_HELD, self::STATUS_REFUSED, self::STATUS_OMITTED,
        self::STATUS_CANCELLED, self::STATUS_NOT_AVAILABLE, self::STATUS_CONTRAINDICATED, self::STATUS_NOT_APPLICABLE,
    ];

    protected $fillable = [
        'company_id', 'branch_id', 'episode_id', 'admission_id', 'encounter_id', 'patient_id',
        'prescription_id', 'prescription_item_id', 'dispensing_item_id', 'medication_id', 'batch_id',
        'scheduled_at', 'administered_at', 'dose', 'dose_unit', 'route', 'site', 'status',
        'reason_if_not_administered', 'is_prn', 'prn_reason', 'administered_by', 'witnessed_by',
        'safety_checks', 'notes', 'superseded_by_correction_id', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'administered_at' => 'datetime',
            'is_prn' => 'boolean',
            'safety_checks' => 'array',
        ];
    }

    public function episode(): BelongsTo
    {
        return $this->belongsTo(NursingEpisode::class, 'episode_id');
    }

    public function admission(): BelongsTo
    {
        return $this->belongsTo(IpdAdmission::class, 'admission_id');
    }

    public function encounter(): BelongsTo
    {
        return $this->belongsTo(Encounter::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class);
    }

    public function prescriptionItem(): BelongsTo
    {
        return $this->belongsTo(PrescriptionItem::class);
    }

    public function dispensingItem(): BelongsTo
    {
        return $this->belongsTo(PharmacyDispensingItem::class, 'dispensing_item_id');
    }

    public function medication(): BelongsTo
    {
        return $this->belongsTo(PharmacyMedication::class, 'medication_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(PharmacyBatch::class, 'batch_id');
    }

    public function administeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'administered_by');
    }

    public function witnessedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'witnessed_by');
    }

    public function corrections(): HasMany
    {
        return $this->hasMany(NursingMedicationAdministrationCorrection::class, 'administration_id');
    }

    public function scopeForTenant($query, int $companyId, ?int $branchId = null)
    {
        return $query->where('company_id', $companyId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
    }
}
