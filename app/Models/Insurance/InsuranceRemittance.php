<?php

namespace App\Models\Insurance;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InsuranceRemittance extends Model
{
    use HasFactory, HasPublicId;

    protected $guarded = ['id', 'public_id'];

    protected $casts = ['amount' => 'decimal:2', 'allocated_amount' => 'decimal:2', 'received_on' => 'date'];

    public function unallocated(): string
    {
        return bcsub((string) $this->amount, (string) $this->allocated_amount, 2);
    }

    /** @return BelongsTo<InsurancePayer, $this> */
    public function payer(): BelongsTo
    {
        return $this->belongsTo(InsurancePayer::class, 'payer_id');
    }

    /** @return HasMany<InsuranceRemittanceLine, $this> */
    public function lines(): HasMany
    {
        return $this->hasMany(InsuranceRemittanceLine::class, 'remittance_id');
    }
}
