<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HrJob extends Model
{
    use HasFactory;

    protected $table = 'hr_jobs';

    protected $guarded = ['id'];

    protected $casts = ['required_credentials' => 'array'];
}
