<?php

namespace App\Models\Integration;

use Illuminate\Database\Eloquent\Model;

class IntegrationAttempt extends Model
{
    protected $table = 'integration_attempts';

    protected $guarded = ['id'];

    public $timestamps = false;

    protected $casts = ['started_at' => 'datetime', 'finished_at' => 'datetime'];
}
