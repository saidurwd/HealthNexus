<?php

namespace App\Models\Portal;

use Illuminate\Database\Eloquent\Model;

class PortalSecurityEvent extends Model
{
    protected $table = 'portal_security_events';

    protected $guarded = ['id'];

    public $timestamps = false;

    protected $casts = ['detail' => 'array', 'created_at' => 'datetime'];
}
