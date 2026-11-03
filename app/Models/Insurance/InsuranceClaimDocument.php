<?php

namespace App\Models\Insurance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InsuranceClaimDocument extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = ['required' => 'boolean', 'received' => 'boolean'];
}
