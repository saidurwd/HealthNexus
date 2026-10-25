<?php

namespace App\Models\Ed;

use Illuminate\Database\Eloquent\Model;

class EdStatusHistory extends Model
{
    protected $table = 'ed_status_history';

    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = ['changed_at' => 'datetime'];
}
