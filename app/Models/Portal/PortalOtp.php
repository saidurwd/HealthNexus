<?php

namespace App\Models\Portal;

use Illuminate\Database\Eloquent\Model;

class PortalOtp extends Model
{
    protected $table = 'portal_otps';

    protected $guarded = ['id'];

    public $timestamps = false;

    protected $casts = ['expires_at' => 'datetime', 'consumed_at' => 'datetime', 'created_at' => 'datetime'];
}
