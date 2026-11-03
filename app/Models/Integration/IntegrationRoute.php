<?php

namespace App\Models\Integration;

use Illuminate\Database\Eloquent\Model;

class IntegrationRoute extends Model
{
    protected $table = 'integration_routes';

    protected $guarded = ['id'];

    protected $casts = ['conditions' => 'array', 'active' => 'boolean'];
}
