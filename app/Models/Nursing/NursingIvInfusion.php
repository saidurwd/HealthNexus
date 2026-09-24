<?php

namespace App\Models\Nursing;

use App\Models\Ipd\IpdAdmission;
use App\Models\PrescriptionItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NursingIvInfusion extends Model
{
    public const STATUS_RUNNING = 'running';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_STOPPED = 'stopped';

    protected $fillable = [
        'company_id', 'branch_id', 'episode_id', 'admission_id', 'prescription_item_id', 'device_id',
        'fluid_name', 'rate', 'start_time', 'expected_completion', 'actual_completion', 'site',
        'volume', 'complications', 'status', 'monitored_by',
    ];

    protected function casts(): array
    {
        return ['start_time' => 'datetime', 'expected_completion' => 'datetime', 'actual_completion' => 'datetime'];
    }

    public function episode(): BelongsTo
    {
        return $this->belongsTo(NursingEpisode::class, 'episode_id');
    }

    public function admission(): BelongsTo
    {
        return $this->belongsTo(IpdAdmission::class, 'admission_id');
    }

    public function prescriptionItem(): BelongsTo
    {
        return $this->belongsTo(PrescriptionItem::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(NursingDevice::class, 'device_id');
    }

    public function monitoredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'monitored_by');
    }

    public function scopeForTenant($query, int $companyId, ?int $branchId = null)
    {
        return $query->where('company_id', $companyId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
    }
}
