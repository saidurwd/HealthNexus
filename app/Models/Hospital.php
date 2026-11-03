<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Hospital extends Model
{
    use HasFactory, SoftDeletes;

    public const DEFAULT_CODE = 'MAIN';

    protected $fillable = [
        'organization_id',
        'name',
        'code',
        'slug',
        'license_number',
        'email',
        'phone',
        'address',
        'settings',
        'is_active',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    /** The hospital that holds branches created without an explicit one. */
    public static function defaultFor(int $organizationId): self
    {
        return static::withTrashed()->firstOrCreate(
            ['organization_id' => $organizationId, 'code' => self::DEFAULT_CODE],
            [
                'name' => Organization::query()->whereKey($organizationId)->value('name') ?? 'Main Hospital',
                'slug' => 'main',
                'is_active' => true,
            ],
        );
    }
}
