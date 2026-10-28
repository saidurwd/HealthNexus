<?php

namespace App\Models\Portal;

use Illuminate\Database\Eloquent\Model;

class PortalReleasePolicy extends Model
{
    protected $table = 'portal_release_policies';

    protected $guarded = ['id'];

    protected $casts = ['sensitive' => 'boolean'];
}
