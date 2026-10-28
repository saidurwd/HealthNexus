<?php

namespace App\Models\Portal;

use Illuminate\Database\Eloquent\Model;

class PortalDevice extends Model
{
    protected $table = 'portal_devices';

    protected $guarded = ['id'];

    protected $casts = ['push_token' => 'encrypted', 'trusted_at' => 'datetime', 'last_seen_at' => 'datetime', 'revoked_at' => 'datetime'];
}
