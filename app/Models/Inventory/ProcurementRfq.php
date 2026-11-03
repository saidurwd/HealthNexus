<?php

namespace App\Models\Inventory;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProcurementRfq extends Model
{
    use HasFactory, HasPublicId;

    protected $table = 'procurement_rfqs';

    protected $guarded = ['id', 'public_id'];

    protected $casts = ['closes_on' => 'date'];

    /** @return HasMany<ProcurementRfqLine, $this> */
    public function lines(): HasMany
    {
        return $this->hasMany(ProcurementRfqLine::class, 'rfq_id');
    }

    /** @return HasMany<ProcurementRfqSupplier, $this> */
    public function invitations(): HasMany
    {
        return $this->hasMany(ProcurementRfqSupplier::class, 'rfq_id');
    }

    /** @return HasMany<ProcurementQuotation, $this> */
    public function quotations(): HasMany
    {
        return $this->hasMany(ProcurementQuotation::class, 'rfq_id');
    }
}
