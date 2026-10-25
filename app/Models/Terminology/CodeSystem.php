<?php

namespace App\Models\Terminology;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CodeSystem extends Model
{
    protected $fillable = ['key', 'uri', 'name', 'version', 'publisher', 'status', 'loaded_at'];

    protected $casts = ['loaded_at' => 'datetime'];

    public function concepts(): HasMany
    {
        return $this->hasMany(CodeConcept::class);
    }
}
