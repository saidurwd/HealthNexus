<?php

namespace App\Models\Ai;

use App\Models\Concerns\HasPublicId;
use Database\Factories\Ai\AiIncidentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiIncident extends Model
{
    use HasFactory, HasPublicId;

    protected static function newFactory(): AiIncidentFactory
    {
        return AiIncidentFactory::new();
    }

    protected $table = 'ai_incidents';

    protected $guarded = ['id'];

    protected $casts = ['closed_at' => 'datetime'];
}
