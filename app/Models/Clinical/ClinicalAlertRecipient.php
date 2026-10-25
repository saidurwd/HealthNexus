<?php

namespace App\Models\Clinical;

use Illuminate\Database\Eloquent\Model;

class ClinicalAlertRecipient extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['notified_at' => 'datetime'];
}
