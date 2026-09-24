<?php

namespace App\Models\Nursing;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NursingDeviceAssessment extends Model
{
    protected $fillable = ['device_id', 'assessed_at', 'assessed_by', 'findings', 'action_taken'];

    protected function casts(): array
    {
        return ['assessed_at' => 'datetime'];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(NursingDevice::class, 'device_id');
    }

    public function assessedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessed_by');
    }
}
