<?php

namespace App\Models\Facility;

use App\Models\Concerns\HasPublicId;
use Database\Factories\Facility\FacAmbulanceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FacAmbulance extends Model
{
    use HasFactory, HasPublicId;

    protected static function newFactory(): FacAmbulanceFactory
    {
        return FacAmbulanceFactory::new();
    }

    protected $table = 'fac_ambulances';

    protected $guarded = ['id'];

    /** @return HasMany<FacAmbulanceTrip, $this> */
    public function trips(): HasMany
    {
        return $this->hasMany(FacAmbulanceTrip::class, 'ambulance_id');
    }
}
