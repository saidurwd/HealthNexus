<?php

namespace App\Models\Terminology;

use Illuminate\Database\Eloquent\Model;

class CodeMap extends Model
{
    protected $fillable = ['from_system_id', 'from_code', 'to_system_id', 'to_code', 'relationship'];
}
