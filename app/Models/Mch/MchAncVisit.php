<?php

namespace App\Models\Mch;

use Database\Factories\Mch\MchAncVisitFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MchAncVisit extends Model
{
    use HasFactory;

    protected static function newFactory(): MchAncVisitFactory
    {
        return MchAncVisitFactory::new();
    }

    protected $table = 'mch_anc_visits';

    protected $guarded = ['id'];

    protected $casts = ['visited_at' => 'datetime', 'danger_signs' => 'array', 'alerts' => 'array', 'tt_given' => 'boolean', 'next_visit_on' => 'date:Y-m-d', 'weight_kg' => 'decimal:1', 'hemoglobin' => 'decimal:1'];

    /** @return BelongsTo<MchPregnancy, $this> */
    public function pregnancy(): BelongsTo
    {
        return $this->belongsTo(MchPregnancy::class, 'pregnancy_id');
    }
}
