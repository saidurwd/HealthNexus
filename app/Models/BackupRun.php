<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BackupRun extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = ['encrypted' => 'boolean', 'details' => 'array', 'started_at' => 'datetime', 'finished_at' => 'datetime', 'signed_off_at' => 'datetime'];
}
