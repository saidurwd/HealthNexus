<?php

namespace App\Models\Clinical;

use Illuminate\Database\Eloquent\Model;

class ClinicalAlertEscalation extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = ['escalated_at' => 'datetime'];
}
