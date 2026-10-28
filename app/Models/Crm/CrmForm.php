<?php

namespace App\Models\Crm;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;

class CrmForm extends Model
{
    use HasPublicId;

    protected $table = 'crm_forms';

    protected $guarded = ['id'];

    protected $casts = ['fields' => 'array'];
}
