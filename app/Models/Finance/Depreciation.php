<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

class Depreciation extends Model
{
    protected $table = 'fin_depreciation';

    protected $guarded = ['id'];

    public $timestamps = false;

    protected $casts = ['amount' => 'decimal:2'];
}
