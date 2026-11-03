<?php

namespace App\Models\Integration;

use Illuminate\Database\Eloquent\Model;

class IntegrationNonce extends Model
{
    protected $table = 'integration_nonces';

    protected $guarded = ['id'];

    public $timestamps = false;

    protected $casts = ['seen_at' => 'datetime'];
}
