<?php

namespace App\Models\Icu;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IcuInfusionTitration extends Model
{
    protected $fillable = [
        'infusion_id', 'previous_rate', 'new_rate', 'rate_unit', 'reason', 'changed_by', 'changed_at',
    ];

    protected function casts(): array
    {
        return ['previous_rate' => 'float', 'new_rate' => 'float', 'changed_at' => 'datetime'];
    }

    public function infusion(): BelongsTo
    {
        return $this->belongsTo(IcuInfusion::class, 'infusion_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
