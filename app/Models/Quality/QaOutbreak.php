<?php

namespace App\Models\Quality;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;

class QaOutbreak extends Model
{
    use HasPublicId;

    protected $table = 'qa_outbreaks';

    protected $guarded = ['id'];

    protected $casts = ['opened_on' => 'date:Y-m-d', 'closed_on' => 'date:Y-m-d'];
}
