<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Model;

class HrDevice extends Model
{
    protected $table = 'hr_devices';

    protected $guarded = ['id'];

    protected $casts = ['last_sync_at' => 'datetime', 'is_active' => 'boolean'];
}
