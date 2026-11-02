<?php

namespace App\Models\Facility;

use Database\Factories\Facility\FacDietTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FacDietType extends Model
{
    use HasFactory;

    protected static function newFactory(): FacDietTypeFactory
    {
        return FacDietTypeFactory::new();
    }

    protected $table = 'fac_diet_types';

    protected $guarded = ['id'];

    protected $casts = ['active' => 'boolean'];
}
