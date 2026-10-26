<?php

namespace App\Models\Blood;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BloodDonorAdverseEvent extends Model
{
    use HasFactory, HasPublicId;

    protected $guarded = ['id', 'public_id'];

    protected $casts = ['occurred_at' => 'datetime'];
}
