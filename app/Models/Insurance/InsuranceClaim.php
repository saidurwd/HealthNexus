<?php

namespace App\Models\Insurance;

use App\Models\Concerns\HasPublicId;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InsuranceClaim extends Model
{
    use HasFactory, HasPublicId;

    protected $guarded = ['id', 'public_id'];

    protected $casts = ['diagnoses' => 'array', 'procedures' => 'array', 'scrub_errors' => 'array', 'service_date' => 'date', 'filing_deadline' => 'date', 'submitted_at' => 'datetime', 'closed_at' => 'datetime', 'gross_amount' => 'decimal:2', 'non_covered_amount' => 'decimal:2', 'deductible_amount' => 'decimal:2', 'copay_amount' => 'decimal:2', 'payer_share' => 'decimal:2', 'patient_share' => 'decimal:2', 'paid_amount' => 'decimal:2', 'adjusted_amount' => 'decimal:2'];

    /** @return BelongsTo<InsurancePayer, $this> */
    public function payer(): BelongsTo
    {
        return $this->belongsTo(InsurancePayer::class, 'payer_id');
    }

    /** @return BelongsTo<Patient, $this> */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /** @return HasMany<InsuranceClaimLine, $this> */
    public function lines(): HasMany
    {
        return $this->hasMany(InsuranceClaimLine::class, 'claim_id');
    }

    /** @return HasMany<InsuranceClaimEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(InsuranceClaimEvent::class, 'claim_id')->orderBy('occurred_at')->orderBy('id');
    }

    /** @return HasMany<InsuranceClaimDocument, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(InsuranceClaimDocument::class, 'claim_id');
    }

    /** @return HasMany<InsuranceClaimQuery, $this> */
    public function queries(): HasMany
    {
        return $this->hasMany(InsuranceClaimQuery::class, 'claim_id');
    }

    public function isLocked(): bool
    {
        return in_array($this->status, config('insurance.locked_states'), true);
    }

    /** What the payer still owes on this claim. */
    public function outstanding(): string
    {
        $owed = bcsub((string) $this->payer_share, bcadd((string) $this->paid_amount, (string) $this->adjusted_amount, 2), 2);

        return bccomp($owed, '0', 2) < 0 ? '0.00' : $owed;
    }
}
