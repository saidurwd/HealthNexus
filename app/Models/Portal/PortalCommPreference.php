<?php

namespace App\Models\Portal;

use Illuminate\Database\Eloquent\Model;

class PortalCommPreference extends Model
{
    protected $table = 'portal_comm_preferences';

    protected $guarded = ['id'];

    protected $casts = ['opted_in' => 'boolean', 'changed_at' => 'datetime'];
}
