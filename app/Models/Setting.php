<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'group',
        'key',
        'value',
        'type',
        'description',
        'is_sensitive',
        'is_locked',
        'sort_order',
    ];

    protected $casts = [
        'is_sensitive' => 'boolean',
        'is_locked' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeNonSensitive($query)
    {
        return $query->where('is_sensitive', false);
    }
}
