<?php

namespace App\Models\Insurance;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InsuranceClaimQuery extends Model
{
    use HasFactory, HasPublicId;

    protected $guarded = ['id', 'public_id'];

    protected $casts = ['due_at' => 'datetime', 'raised_at' => 'datetime', 'answered_at' => 'datetime'];
}
