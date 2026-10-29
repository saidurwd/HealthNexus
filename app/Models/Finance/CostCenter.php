<?php

namespace App\Models\Finance;

use App\Models\Concerns\HasPublicId;
use Database\Factories\Finance\CostCenterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A place costs and revenue are traced to: a department, a branch or a service line. */
class CostCenter extends Model
{
    use HasFactory, HasPublicId;

    protected static function newFactory(): CostCenterFactory
    {
        return CostCenterFactory::new();
    }

    protected $table = 'fin_cost_centers';

    protected $guarded = ['id'];

    protected $casts = ['active' => 'boolean'];

    /** @return BelongsTo<CostCenter, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }
}
