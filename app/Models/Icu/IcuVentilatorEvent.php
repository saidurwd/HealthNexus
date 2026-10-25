<?php

namespace App\Models\Icu;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IcuVentilatorEvent extends Model
{
    public const TYPE_INITIATED = 'initiated';

    public const TYPE_MODE_CHANGED = 'mode_changed';

    public const TYPE_PARAMETER_CHANGED = 'parameter_changed';

    public const TYPE_WEANING_TRIAL = 'weaning_trial';

    public const TYPE_EXTUBATED = 'extubated';

    public const TYPE_RE_INTUBATED = 're_intubated';

    protected $fillable = [
        'organization_id', 'branch_id', 'episode_id', 'event_type', 'mode', 'fio2', 'peep',
        'tidal_volume', 'respiratory_rate', 'pressure_support', 'peak_pressure', 'plateau_pressure',
        'mean_airway_pressure', 'ie_ratio', 'notes', 'recorded_by', 'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'fio2' => 'float', 'peep' => 'float', 'tidal_volume' => 'float', 'respiratory_rate' => 'float',
            'pressure_support' => 'float', 'peak_pressure' => 'float', 'plateau_pressure' => 'float',
            'mean_airway_pressure' => 'float', 'recorded_at' => 'datetime',
        ];
    }

    public function episode(): BelongsTo
    {
        return $this->belongsTo(IcuEpisode::class, 'episode_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function scopeForTenant($query, int $organizationId, ?int $branchId = null)
    {
        return $query->where('organization_id', $organizationId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
    }
}
