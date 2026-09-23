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
        'company_id',
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

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function scopeForCompany($query, ?int $companyId)
    {
        return $query->where(fn ($q) => $q->whereNull('company_id')->orWhere('company_id', $companyId));
    }
}
