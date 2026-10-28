<?php

namespace App\Models\Portal;

use Illuminate\Database\Eloquent\Model;

class PortalOutbox extends Model
{
    protected $table = 'portal_outbox';

    protected $guarded = ['id'];

    protected $casts = ['send_after' => 'datetime', 'sent_at' => 'datetime'];
}
