<?php

namespace App\Models\Ipd;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class IpdRoom extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id', 'branch_id', 'ward_id', 'room_number', 'room_type', 'capacity',
        'gender_policy', 'isolation_capable', 'is_vip', 'rate_category', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'isolation_capable' => 'boolean',
            'is_vip' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo { return $this->belongsTo(\App\Models\Company::class); }
    public function branch(): BelongsTo { return $this->belongsTo(\App\Models\Branch::class); }
    public function ward(): BelongsTo { return $this->belongsTo(IpdWard::class, 'ward_id'); }
    public function beds(): HasMany { return $this->hasMany(IpdBed::class, 'room_id'); }

    public function scopeForTenant($query, int $companyId, ?int $branchId = null)
    {
        return $query->where('company_id', $companyId)->where(fn ($q) => $q->where('branch_id', $branchId)->orWhereNull('branch_id'));
    }
}
