<?php

namespace App\Models\Laboratory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabCriticalValue extends Model
{
    protected $fillable = ['test_id', 'low_threshold', 'high_threshold', 'effective_from', 'effective_to', 'is_active'];

    protected function casts(): array
    {
        return [
            'low_threshold' => 'decimal:4',
            'high_threshold' => 'decimal:4',
            'effective_from' => 'date',
            'effective_to' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function test(): BelongsTo { return $this->belongsTo(LabTest::class, 'test_id'); }
}
