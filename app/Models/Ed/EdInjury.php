<?php

namespace App\Models\Ed;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;

class EdInjury extends Model
{
    use HasPublicId;

    protected $guarded = ['id', 'public_id'];
}
