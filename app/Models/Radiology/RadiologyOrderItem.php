<?php

namespace App\Models\Radiology;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RadiologyOrderItem extends Model
{
    protected $fillable = [
        'radiology_order_id', 'procedure_id', 'protocol_id', 'body_part_id', 'laterality',
        'requested_procedure_name', 'priority', 'status', 'result_status', 'requested_at',
    ];

    protected function casts(): array
    {
        return ['requested_at' => 'datetime'];
    }

    public function radiologyOrder(): BelongsTo { return $this->belongsTo(RadiologyOrder::class, 'radiology_order_id'); }
    public function procedure(): BelongsTo { return $this->belongsTo(RadiologyProcedure::class, 'procedure_id'); }
    public function protocol(): BelongsTo { return $this->belongsTo(RadiologyProtocol::class, 'protocol_id'); }
    public function bodyPart(): BelongsTo { return $this->belongsTo(RadiologyBodyPart::class, 'body_part_id'); }
    public function examination(): HasOne { return $this->hasOne(RadiologyExamination::class, 'order_item_id'); }

    public function isUnmatched(): bool { return $this->procedure_id === null; }
    public function isCancelled(): bool { return $this->status === 'cancelled'; }
}
