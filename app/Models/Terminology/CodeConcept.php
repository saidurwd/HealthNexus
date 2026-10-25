<?php

namespace App\Models\Terminology;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CodeConcept extends Model
{
    protected $fillable = ['code_system_id', 'code', 'display_en', 'display_bn', 'parent_code', 'status', 'valid_from', 'valid_to'];

    public function system(): BelongsTo
    {
        return $this->belongsTo(CodeSystem::class, 'code_system_id');
    }
}
