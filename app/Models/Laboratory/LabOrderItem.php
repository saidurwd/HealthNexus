<?php

namespace App\Models\Laboratory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabOrderItem extends Model
{
    protected $fillable = [
        'lab_order_id', 'test_id', 'panel_id', 'specimen_id', 'requested_test_name',
        'priority', 'status', 'result_status', 'requested_at', 'collected_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return ['requested_at' => 'datetime', 'collected_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    public function labOrder(): BelongsTo { return $this->belongsTo(LabOrder::class, 'lab_order_id'); }
    public function test(): BelongsTo { return $this->belongsTo(LabTest::class, 'test_id'); }
    public function panel(): BelongsTo { return $this->belongsTo(LabPanel::class, 'panel_id'); }
    public function specimen(): BelongsTo { return $this->belongsTo(LabSpecimen::class, 'specimen_id'); }
    public function results(): HasMany { return $this->hasMany(LabResult::class, 'lab_order_item_id'); }

    public function currentResult(): HasMany { return $this->results()->where('is_current', true); }

    public function isUnmatched(): bool { return $this->test_id === null; }
    public function isCancelled(): bool { return $this->status === 'cancelled'; }
}
