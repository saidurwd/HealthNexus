<?php

namespace App\Models\Icu;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IcuChargeEvent extends Model
{
    protected $fillable = ['episode_id', 'charge_type', 'charge_date'];

    protected function casts(): array
    {
        return ['charge_date' => 'date'];
    }

    public function episode(): BelongsTo { return $this->belongsTo(IcuEpisode::class, 'episode_id'); }
}
