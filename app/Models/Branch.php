<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'hospital_id',
        'name',
        'code',
        'slug',
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

    protected static function booted(): void
    {
        // Organization → Hospital → Branch: a branch always belongs to a hospital.
        static::creating(function (Branch $branch) {
            $branch->hospital_id ??= Hospital::defaultFor($branch->organization_id)->id;
        });
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_branches')
            ->withPivot('access_level', 'is_default', 'metadata')
            ->withTimestamps();
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }
}
