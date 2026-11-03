<?php

namespace App\Models\Integration;

use Illuminate\Database\Eloquent\Model;

class IntegrationCredential extends Model
{
    protected $table = 'integration_credentials';

    protected $guarded = ['id'];

    protected $casts = ['secret' => 'encrypted', 'valid_from' => 'datetime', 'valid_to' => 'datetime', 'last_used_at' => 'datetime'];
}
