<?php

namespace App\Models\Facility;

use App\Models\Concerns\HasPublicId;
use Database\Factories\Facility\FacInstrumentSetFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FacInstrumentSet extends Model
{
    use HasFactory, HasPublicId;

    protected static function newFactory(): FacInstrumentSetFactory
    {
        return FacInstrumentSetFactory::new();
    }

    protected $table = 'fac_instrument_sets';

    protected $guarded = ['id'];

    protected $casts = ['active' => 'boolean'];
}
