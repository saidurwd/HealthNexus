<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

class Cursor extends Model
{
    protected $table = 'fin_cursors';

    protected $guarded = ['id'];
}
