<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClinicalOrderItem extends Model
{
    protected $fillable = [
        'clinical_order_id',
        'company_id',
        'item_name',
        'item_code',
        'quantity',
        'notes',
        'status',
    ];

    public function clinicalOrder(): BelongsTo
    {
        return $this->belongsTo(ClinicalOrder::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
