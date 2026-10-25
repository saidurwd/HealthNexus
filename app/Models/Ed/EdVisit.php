<?php

namespace App\Models\Ed;

use App\Models\Branch;
use App\Models\Concerns\HasPublicId;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class EdVisit extends Model
{
    use HasPublicId;

    public const AWAITING_TRIAGE = 'awaiting_triage';

    public const WAITING = 'waiting';

    public const IN_TREATMENT = 'in_treatment';

    public const OBSERVATION = 'observation';

    public const AWAITING_DISPOSITION = 'awaiting_disposition';

    public const DISPOSITIONED = 'dispositioned';

    public const CANCELLED = 'cancelled';

    protected $guarded = ['id', 'public_id'];

    protected $casts = [
        'arrival_at' => 'datetime', 'triage_due_at' => 'datetime', 'triaged_at' => 'datetime', 'doctor_due_at' => 'datetime',
        'retriage_due_at' => 'datetime', 'doctor_seen_at' => 'datetime', 'dispositioned_at' => 'datetime',
        'is_unknown_patient' => 'boolean', 'is_mass_casualty' => 'boolean',
    ];

    /** @return BelongsTo<Patient, $this> */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /** @return BelongsTo<Encounter, $this> */
    public function encounter(): BelongsTo
    {
        return $this->belongsTo(Encounter::class);
    }

    /** @return BelongsTo<Branch, $this> */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /** @return BelongsTo<EdZone, $this> */
    public function zone(): BelongsTo
    {
        return $this->belongsTo(EdZone::class, 'zone_id');
    }

    /** @return BelongsTo<EdBay, $this> */
    public function bay(): BelongsTo
    {
        return $this->belongsTo(EdBay::class, 'bay_id');
    }

    /** @return BelongsTo<User, $this> */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    /** @return BelongsTo<User, $this> */
    public function nurse(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nurse_id');
    }

    /** @return HasMany<EdResusEvent, $this> */
    public function resusEvents(): HasMany
    {
        return $this->hasMany(EdResusEvent::class, 'visit_id')->orderBy('occurred_at');
    }

    /** @return HasMany<EdProtocolRun, $this> */
    public function protocolRuns(): HasMany
    {
        return $this->hasMany(EdProtocolRun::class, 'visit_id')->orderBy('started_at');
    }

    /** @return HasMany<EdInjury, $this> */
    public function injuries(): HasMany
    {
        return $this->hasMany(EdInjury::class, 'visit_id')->orderBy('id');
    }

    /** @return HasMany<EdConsult, $this> */
    public function consults(): HasMany
    {
        return $this->hasMany(EdConsult::class, 'visit_id')->orderBy('requested_at');
    }

    /** @return HasMany<EdObservationStay, $this> */
    public function observationStays(): HasMany
    {
        return $this->hasMany(EdObservationStay::class, 'visit_id')->orderBy('started_at');
    }

    /** @return HasOne<EdDisposition, $this> */
    public function disposition(): HasOne
    {
        return $this->hasOne(EdDisposition::class, 'visit_id');
    }

    public function triageRecords(): HasMany
    {
        return $this->hasMany(EdTriageRecord::class, 'visit_id');
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(EdStatusHistory::class, 'visit_id');
    }

    public function isActive(): bool
    {
        return ! in_array($this->status, config('ed.closed_statuses'), true);
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', config('ed.closed_statuses'));
    }

    public function scopeForTenant($query, int $organizationId, ?int $branchId = null)
    {
        return $query->where('organization_id', $organizationId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
    }
}
