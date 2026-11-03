<?php

namespace App\Models\Facility;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FacWasteManifest extends Model
{
    use HasPublicId;

    protected $table = 'fac_waste_manifests';

    protected $guarded = ['id'];

    protected $casts = ['collected_on' => 'date:Y-m-d', 'disposed_on' => 'date:Y-m-d', 'total_kg' => 'decimal:2'];

    /** @return HasMany<FacWasteRecord, $this> */
    public function records(): HasMany
    {
        return $this->hasMany(FacWasteRecord::class, 'manifest_id');
    }
}
