<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IdentificationType extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'issuing_authority',
        'is_primary',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
