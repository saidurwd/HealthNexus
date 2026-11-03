<?php

namespace App\Models\Geo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A place in the country's administrative geography (national reference data, shared by every hospital). */
class GeoDistrict extends Model
{
    protected $table = 'geo_districts';

    protected $guarded = ['id'];

    /** The name in the language the person reads: Bangla when the screen is in Bangla, otherwise English. */
    public function displayName(): string
    {
        return app()->getLocale() === 'bn' && filled($this->name_bn) ? (string) $this->name_bn : (string) $this->name_en;
    }

    /** @return BelongsTo<GeoDivision, $this> */
    public function division(): BelongsTo
    {
        return $this->belongsTo(GeoDivision::class, 'division_id');
    }

    /** @return HasMany<GeoUpazila, $this> */
    public function upazilas(): HasMany
    {
        return $this->hasMany(GeoUpazila::class, 'district_id');
    }
}
