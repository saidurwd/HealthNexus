<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HrShift extends Model
{
    use HasFactory;

    protected $table = 'hr_shifts';

    protected $guarded = ['id'];

    protected $casts = ['is_night' => 'boolean'];
}
