<?php

namespace App\Models\Geo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A place in the country's administrative geography (national reference data, shared by every hospital). */
class GeoUpazila extends Model
{
    protected $table = 'geo_upazilas';

    protected $guarded = ['id'];

    /** The name in the language the person reads: Bangla when the screen is in Bangla, otherwise English. */
    public function displayName(): string
    {
        return app()->getLocale() === 'bn' && filled($this->name_bn) ? (string) $this->name_bn : (string) $this->name_en;
    }

    /** @return BelongsTo<GeoDistrict, $this> */
    public function district(): BelongsTo
    {
        return $this->belongsTo(GeoDistrict::class, 'district_id');
    }

    /** @return HasMany<GeoUnion, $this> */
    public function unions(): HasMany
    {
        return $this->hasMany(GeoUnion::class, 'upazila_id');
    }
}
