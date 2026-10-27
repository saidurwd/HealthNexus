<?php

namespace App\Models\Insurance;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InsurancePayer extends Model
{
    use HasFactory, HasPublicId;

    protected $guarded = ['id', 'public_id'];

    protected $casts = ['preauth_required_above' => 'decimal:2'];

    /** @return HasMany<InsurancePlan, $this> */
    public function plans(): HasMany
    {
        return $this->hasMany(InsurancePlan::class, 'payer_id');
    }
}
