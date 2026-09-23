<?php

namespace App\Models\Radiology;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RadiologyProtocol extends Model
{
    protected $fillable = ['procedure_id', 'code', 'name', 'description', 'contrast_required', 'preparation_instructions', 'is_active'];

    protected function casts(): array
    {
        return ['contrast_required' => 'boolean', 'is_active' => 'boolean'];
    }

    public function procedure(): BelongsTo { return $this->belongsTo(RadiologyProcedure::class, 'procedure_id'); }
}
