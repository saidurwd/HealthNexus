<?php

namespace App\Models\Blood;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BloodSample extends Model
{
    use HasFactory, HasPublicId;

    protected $guarded = ['id', 'public_id'];

    protected $casts = ['collected_at' => 'datetime', 'received_at' => 'datetime', 'expires_at' => 'datetime'];
}
