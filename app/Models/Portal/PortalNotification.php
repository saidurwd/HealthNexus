<?php

namespace App\Models\Portal;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;

class PortalNotification extends Model
{
    use HasPublicId;

    protected $table = 'portal_notifications';

    protected $guarded = ['id'];

    protected $casts = ['read_at' => 'datetime'];
}
