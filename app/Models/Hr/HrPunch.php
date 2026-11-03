<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Model;

class HrPunch extends Model
{
    protected $table = 'hr_punches';

    protected $guarded = ['id'];

    public $timestamps = false;

    protected $casts = ['punched_at' => 'datetime', 'processed_at' => 'datetime'];
}
