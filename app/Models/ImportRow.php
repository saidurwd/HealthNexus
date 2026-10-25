<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportRow extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = ['raw' => 'array', 'data' => 'array', 'errors' => 'array'];
}
