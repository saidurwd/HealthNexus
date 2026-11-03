<?php

namespace App\Models\Blood;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BloodTemperatureLog extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = ['recorded_at' => 'datetime', 'is_excursion' => 'boolean', 'celsius' => 'float'];
}
