<?php

namespace App\Models\Welfare;

use App\Models\Concerns\HasPublicId;
use Database\Factories\Welfare\WfDonorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WfDonor extends Model
{
    use HasFactory, HasPublicId;

    protected static function newFactory(): WfDonorFactory
    {
        return WfDonorFactory::new();
    }

    protected $table = 'wf_donors';

    protected $guarded = ['id'];

    protected $casts = ['anonymous' => 'boolean'];

    /** @return HasMany<WfFundEntry, $this> */
    public function entries(): HasMany
    {
        return $this->hasMany(WfFundEntry::class, 'donor_id');
    }
}
