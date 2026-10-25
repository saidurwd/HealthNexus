<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppointmentType extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'code',
        'name',
        'color',
        'is_follow_up_type',
        'is_telemedicine_type',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_follow_up_type' => 'boolean',
        'is_telemedicine_type' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function scopeForOrganization($query, ?int $organizationId)
    {
        return $query->where(fn ($q) => $q->whereNull('organization_id')->orWhere('organization_id', $organizationId));
    }
}
