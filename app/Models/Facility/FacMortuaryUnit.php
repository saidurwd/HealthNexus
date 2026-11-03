<?php

namespace App\Models\Facility;

use Database\Factories\Facility\FacMortuaryUnitFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FacMortuaryUnit extends Model
{
    use HasFactory;

    protected static function newFactory(): FacMortuaryUnitFactory
    {
        return FacMortuaryUnitFactory::new();
    }

    protected $table = 'fac_mortuary_units';

    protected $guarded = ['id'];

    protected $casts = ['active' => 'boolean'];
}
