<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Model;

class HrDocument extends Model
{
    protected $table = 'hr_documents';

    protected $guarded = ['id'];

    protected $casts = ['expires_on' => 'date:Y-m-d'];
}
