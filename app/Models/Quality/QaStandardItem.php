<?php

namespace App\Models\Quality;

use Illuminate\Database\Eloquent\Model;

class QaStandardItem extends Model
{
    protected $table = 'qa_standard_items';

    protected $guarded = ['id'];

    public $timestamps = false;
}
