<?php

namespace App\Models\Finance;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Writeoff extends Model
{
    use HasPublicId;

    protected $table = 'fin_writeoffs';

    protected $guarded = ['id'];

    protected $casts = ['amount' => 'decimal:2', 'decided_at' => 'datetime'];

    /** @return BelongsTo<Receivable, $this> */
    public function receivable(): BelongsTo
    {
        return $this->belongsTo(Receivable::class, 'receivable_id');
    }
}
