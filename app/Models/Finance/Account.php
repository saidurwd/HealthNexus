<?php

namespace App\Models\Finance;

use App\Models\Concerns\HasPublicId;
use Database\Factories\Finance\AccountFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** One account of the chart. A heading (not postable) only groups; postings go to leaf accounts. */
class Account extends Model
{
    use HasFactory, HasPublicId;

    protected static function newFactory(): AccountFactory
    {
        return AccountFactory::new();
    }

    protected $table = 'fin_accounts';

    protected $guarded = ['id'];

    protected $casts = ['postable' => 'boolean', 'active' => 'boolean', 'system' => 'boolean'];

    /** @return BelongsTo<Account, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** @return HasMany<Account, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('code');
    }
}
