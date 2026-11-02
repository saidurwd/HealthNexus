<?php

namespace App\Models\Facility;

use App\Models\Concerns\HasPublicId;
use App\Models\User;
use Database\Factories\Facility\FacWorkOrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FacWorkOrder extends Model
{
    use HasFactory, HasPublicId;

    protected static function newFactory(): FacWorkOrderFactory
    {
        return FacWorkOrderFactory::new();
    }

    protected $table = 'fac_work_orders';

    protected $guarded = ['id'];

    protected $casts = ['reported_at' => 'datetime', 'due_on' => 'date:Y-m-d', 'started_at' => 'datetime', 'completed_at' => 'datetime', 'verified_at' => 'datetime', 'cost' => 'decimal:2'];

    /** @return BelongsTo<FacAsset, $this> */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(FacAsset::class, 'asset_id');
    }

    /** @return HasMany<FacWorkOrderPart, $this> */
    public function parts(): HasMany
    {
        return $this->hasMany(FacWorkOrderPart::class, 'work_order_id');
    }

    /** @return BelongsTo<User, $this> */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /** @return BelongsTo<User, $this> */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }
}
