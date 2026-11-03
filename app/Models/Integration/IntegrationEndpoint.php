<?php

namespace App\Models\Integration;

use Illuminate\Database\Eloquent\Model;

class IntegrationEndpoint extends Model
{
    protected $table = 'integration_endpoints';

    protected $guarded = ['id'];

    protected $casts = ['active' => 'boolean'];
}
