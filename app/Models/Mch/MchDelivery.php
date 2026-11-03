<?php

namespace App\Models\Mch;

use App\Models\Concerns\HasPublicId;
use Database\Factories\Mch\MchDeliveryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MchDelivery extends Model
{
    use HasFactory, HasPublicId;

    protected static function newFactory(): MchDeliveryFactory
    {
        return MchDeliveryFactory::new();
    }

    protected $table = 'mch_deliveries';

    protected $guarded = ['id'];

    protected $casts = ['delivered_at' => 'datetime', 'complications' => 'array'];

    /** @return BelongsTo<MchPregnancy, $this> */
    public function pregnancy(): BelongsTo
    {
        return $this->belongsTo(MchPregnancy::class, 'pregnancy_id');
    }

    /** @return HasMany<MchNewborn, $this> */
    public function newborns(): HasMany
    {
        return $this->hasMany(MchNewborn::class, 'delivery_id');
    }
}
