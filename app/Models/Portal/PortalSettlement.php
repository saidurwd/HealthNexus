<?php

namespace App\Models\Portal;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PortalSettlement extends Model
{
    use HasPublicId;

    protected $table = 'portal_settlements';

    protected $guarded = ['id'];

    protected $casts = ['period_from' => 'date:Y-m-d', 'period_to' => 'date:Y-m-d', 'total' => 'decimal:2', 'fees' => 'decimal:2'];

    /** @return HasMany<PortalSettlementLine, $this> */
    public function lines(): HasMany
    {
        return $this->hasMany(PortalSettlementLine::class, 'settlement_id');
    }
}
