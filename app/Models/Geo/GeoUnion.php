<?php

namespace App\Models\Geo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A place in the country's administrative geography (national reference data, shared by every hospital). */
class GeoUnion extends Model
{
    protected $table = 'geo_unions';

    protected $guarded = ['id'];

    /** The name in the language the person reads: Bangla when the screen is in Bangla, otherwise English. */
    public function displayName(): string
    {
        return app()->getLocale() === 'bn' && filled($this->name_bn) ? (string) $this->name_bn : (string) $this->name_en;
    }

    /** @return BelongsTo<GeoUpazila, $this> */
    public function upazila(): BelongsTo
    {
        return $this->belongsTo(GeoUpazila::class, 'upazila_id');
    }
}
