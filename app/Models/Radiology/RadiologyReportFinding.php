<?php

namespace App\Models\Radiology;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RadiologyReportFinding extends Model
{
    protected $fillable = ['report_id', 'organ', 'finding_text', 'measurement_value', 'measurement_unit', 'laterality', 'is_critical'];

    protected function casts(): array
    {
        return ['measurement_value' => 'decimal:3', 'is_critical' => 'boolean'];
    }

    public function report(): BelongsTo { return $this->belongsTo(RadiologyReport::class, 'report_id'); }
}
