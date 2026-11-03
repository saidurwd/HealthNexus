<?php

namespace App\Models\Ai;

use Illuminate\Database\Eloquent\Model;

class AiPrompt extends Model
{
    protected $table = 'ai_prompts';

    protected $guarded = ['id'];
}
