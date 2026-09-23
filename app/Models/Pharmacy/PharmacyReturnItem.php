<?php

namespace App\Models\Pharmacy;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PharmacyReturnItem extends Model
{
    protected $fillable = ['return_id', 'dispensing_item_id', 'quantity_returned', 'returnable_to_stock'];

    protected function casts(): array
    {
        return [
            'quantity_returned' => 'integer',
            'returnable_to_stock' => 'boolean',
        ];
    }

    public function pharmacyReturn(): BelongsTo { return $this->belongsTo(PharmacyReturn::class, 'return_id'); }
    public function dispensingItem(): BelongsTo { return $this->belongsTo(PharmacyDispensingItem::class, 'dispensing_item_id'); }
}
