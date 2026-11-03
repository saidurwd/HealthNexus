<?php

namespace App\Models\Ai;

use App\Models\Concerns\HasPublicId;
use Database\Factories\Ai\AiKnowledgeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiKnowledge extends Model
{
    use HasFactory, HasPublicId;

    protected static function newFactory(): AiKnowledgeFactory
    {
        return AiKnowledgeFactory::new();
    }

    protected $table = 'ai_knowledge';

    protected $guarded = ['id'];

    protected $casts = ['approved_at' => 'datetime'];
}
