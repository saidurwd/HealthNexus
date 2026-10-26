<?php

namespace App\Models\Blood;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BloodDiscard extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = ['discarded_at' => 'datetime'];
}
