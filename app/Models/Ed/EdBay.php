<?php

namespace App\Models\Ed;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EdBay extends Model
{
    use HasFactory, HasPublicId;

    public const AVAILABLE = 'available';

    public const OCCUPIED = 'occupied';

    public const CLEANING = 'cleaning';

    public const BLOCKED = 'blocked';

    protected $guarded = ['id', 'public_id'];

    protected $casts = ['is_active' => 'boolean'];

    /** @return BelongsTo<EdZone, $this> */
    public function zone(): BelongsTo
    {
        return $this->belongsTo(EdZone::class, 'zone_id');
    }

    /** @return BelongsTo<EdVisit, $this> */
    public function visit(): BelongsTo
    {
        return $this->belongsTo(EdVisit::class, 'current_visit_id');
    }
}
