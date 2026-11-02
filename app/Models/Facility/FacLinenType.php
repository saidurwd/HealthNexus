<?php

namespace App\Models\Facility;

use Database\Factories\Facility\FacLinenTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FacLinenType extends Model
{
    use HasFactory;

    protected static function newFactory(): FacLinenTypeFactory
    {
        return FacLinenTypeFactory::new();
    }

    protected $table = 'fac_linen_types';

    protected $guarded = ['id'];

    protected $casts = ['active' => 'boolean'];
}
