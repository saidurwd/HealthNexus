<?php

namespace App\Models\Icu;

use App\Models\Concerns\HasPublicId;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IcuInfusion extends Model
{
    use HasPublicId;

    public const STATUS_RUNNING = 'running';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_STOPPED = 'stopped';

    protected $fillable = [
        'organization_id', 'branch_id', 'episode_id', 'medication', 'concentration', 'initial_rate',
        'rate_unit', 'route', 'target', 'prescriber_id', 'device_id', 'started_at', 'stopped_at',
        'status', 'started_by', 'stopped_by',
    ];

    protected function casts(): array
    {
        return [
            'initial_rate' => 'float', 'target' => 'float',
            'started_at' => 'datetime', 'stopped_at' => 'datetime',
        ];
    }

    public function episode(): BelongsTo
    {
        return $this->belongsTo(IcuEpisode::class, 'episode_id');
    }

    public function prescriber(): BelongsTo
    {
        return $this->belongsTo(Provider::class, 'prescriber_id');
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(IcuDevice::class, 'device_id');
    }

    public function startedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by');
    }

    public function stoppedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'stopped_by');
    }

    public function titrations(): HasMany
    {
        return $this->hasMany(IcuInfusionTitration::class, 'infusion_id');
    }

    public function currentRate(): ?float
    {
        return $this->titrations()->latest('changed_at')->first()?->new_rate ?? $this->initial_rate;
    }

    public function scopeForTenant($query, int $organizationId, ?int $branchId = null)
    {
        return $query->where('organization_id', $organizationId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
    }
}
