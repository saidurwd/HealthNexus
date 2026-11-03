<?php

namespace App\Models\Insurance;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InsurancePlan extends Model
{
    use HasFactory, HasPublicId;

    protected $guarded = ['id', 'public_id'];

    protected $casts = ['copay_percent' => 'decimal:2', 'deductible_amount' => 'decimal:2', 'annual_limit' => 'decimal:2', 'per_visit_limit' => 'decimal:2', 'room_rent_limit_per_day' => 'decimal:2', 'requires_preauth_for_admission' => 'boolean', 'is_network_only' => 'boolean', 'effective_from' => 'date', 'effective_to' => 'date'];

    /** @return BelongsTo<InsurancePayer, $this> */
    public function payer(): BelongsTo
    {
        return $this->belongsTo(InsurancePayer::class, 'payer_id');
    }

    /** @return HasMany<InsurancePlanRule, $this> */
    public function rules(): HasMany
    {
        return $this->hasMany(InsurancePlanRule::class, 'plan_id');
    }
}
