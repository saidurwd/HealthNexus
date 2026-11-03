<?php

namespace App\Models\Blood;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BloodUnitTest extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = ['resulted_at' => 'datetime'];
}
