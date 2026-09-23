<?php

namespace App\Models\Radiology;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RadiologyInstance extends Model
{
    protected $fillable = ['series_id', 'sop_instance_uid', 'sop_class_uid', 'instance_number', 'file_reference'];

    public function series(): BelongsTo { return $this->belongsTo(RadiologySeries::class, 'series_id'); }
}
