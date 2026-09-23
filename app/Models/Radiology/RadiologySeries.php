<?php

namespace App\Models\Radiology;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RadiologySeries extends Model
{
    protected $fillable = ['study_id', 'series_instance_uid', 'series_number', 'series_description', 'modality', 'body_part', 'number_of_instances'];

    protected function casts(): array
    {
        return ['number_of_instances' => 'integer'];
    }

    public function study(): BelongsTo { return $this->belongsTo(RadiologyStudy::class, 'study_id'); }
    public function instances(): HasMany { return $this->hasMany(RadiologyInstance::class, 'series_id'); }
}
