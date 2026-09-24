<?php

namespace App\Models\Ipd;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IpdBedChargeEvent extends Model
{
    protected $fillable = ['admission_id', 'bed_id', 'charge_date'];

    protected function casts(): array
    {
        return ['charge_date' => 'date'];
    }

    public function admission(): BelongsTo { return $this->belongsTo(IpdAdmission::class, 'admission_id'); }
    public function bed(): BelongsTo { return $this->belongsTo(IpdBed::class, 'bed_id'); }
}
