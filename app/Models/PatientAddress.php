<?php

namespace App\Models;

use App\Models\Geo\GeoDistrict;
use App\Models\Geo\GeoDivision;
use App\Models\Geo\GeoUnion;
use App\Models\Geo\GeoUpazila;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PatientAddress extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'patient_id',
        'address_type',
        'line1',
        'line2',
        'city',
        'country_id',
        'state_id',
        'district',
        'division_id',
        'district_id',
        'upazila_id',
        'union_id',
        'village_area',
        'postal_code',
        'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /** @return BelongsTo<GeoDivision, $this> */
    public function geoDivision(): BelongsTo
    {
        return $this->belongsTo(GeoDivision::class, 'division_id');
    }

    /** @return BelongsTo<GeoDistrict, $this> */
    public function geoDistrict(): BelongsTo
    {
        return $this->belongsTo(GeoDistrict::class, 'district_id');
    }

    /** @return BelongsTo<GeoUpazila, $this> */
    public function geoUpazila(): BelongsTo
    {
        return $this->belongsTo(GeoUpazila::class, 'upazila_id');
    }

    /** @return BelongsTo<GeoUnion, $this> */
    public function geoUnion(): BelongsTo
    {
        return $this->belongsTo(GeoUnion::class, 'union_id');
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }
}
