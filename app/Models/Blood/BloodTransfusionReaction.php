<?php

namespace App\Models\Blood;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BloodTransfusionReaction extends Model
{
    use HasFactory, HasPublicId;

    protected $guarded = ['id', 'public_id'];

    protected $casts = ['symptoms' => 'array', 'detected_at' => 'datetime'];
}
