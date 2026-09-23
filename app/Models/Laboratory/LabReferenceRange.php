<?php

namespace App\Models\Laboratory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabReferenceRange extends Model
{
    protected $fillable = [
        'test_id', 'specimen_type_id', 'gender', 'age_min_years', 'age_max_years',
        'pregnancy_status', 'unit', 'low', 'high', 'text_range',
        'effective_from', 'effective_to', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'age_min_years' => 'integer',
            'age_max_years' => 'integer',
            'low' => 'decimal:4',
            'high' => 'decimal:4',
            'effective_from' => 'date',
            'effective_to' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function test(): BelongsTo { return $this->belongsTo(LabTest::class, 'test_id'); }
    public function specimenType(): BelongsTo { return $this->belongsTo(LabSpecimenType::class, 'specimen_type_id'); }
}
