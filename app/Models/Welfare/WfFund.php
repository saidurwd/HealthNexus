<?php

namespace App\Models\Welfare;

use App\Models\Concerns\HasPublicId;
use Database\Factories\Welfare\WfFundFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WfFund extends Model
{
    use HasFactory, HasPublicId;

    protected static function newFactory(): WfFundFactory
    {
        return WfFundFactory::new();
    }

    protected $table = 'wf_funds';

    protected $guarded = ['id'];

    protected $casts = ['per_grant_cap' => 'decimal:2', 'low_balance_alert' => 'decimal:2'];

    /** @return HasMany<WfFundEntry, $this> */
    public function entries(): HasMany
    {
        return $this->hasMany(WfFundEntry::class, 'fund_id');
    }

    /** @return HasMany<WfGrant, $this> */
    public function grants(): HasMany
    {
        return $this->hasMany(WfGrant::class, 'fund_id');
    }
}
