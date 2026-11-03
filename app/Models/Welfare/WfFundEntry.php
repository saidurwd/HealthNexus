<?php

namespace App\Models\Welfare;

use App\Models\Concerns\HasPublicId;
use Database\Factories\Welfare\WfFundEntryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WfFundEntry extends Model
{
    use HasFactory, HasPublicId;

    protected static function newFactory(): WfFundEntryFactory
    {
        return WfFundEntryFactory::new();
    }

    protected $table = 'wf_fund_entries';

    protected $guarded = ['id'];

    public const UPDATED_AT = null;

    protected $casts = ['amount' => 'decimal:2', 'occurred_at' => 'datetime'];

    /** @return BelongsTo<WfFund, $this> */
    public function fund(): BelongsTo
    {
        return $this->belongsTo(WfFund::class, 'fund_id');
    }

    /** @return BelongsTo<WfDonor, $this> */
    public function donor(): BelongsTo
    {
        return $this->belongsTo(WfDonor::class, 'donor_id');
    }
}
