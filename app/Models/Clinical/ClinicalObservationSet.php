<?php

namespace App\Models\Clinical;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClinicalObservationSet extends Model
{
    use HasPublicId;

    protected $guarded = ['id', 'public_id'];

    protected $casts = ['effective_at' => 'datetime'];

    public function observations(): HasMany
    {
        return $this->hasMany(ClinicalObservation::class, 'observation_set_id');
    }
}
