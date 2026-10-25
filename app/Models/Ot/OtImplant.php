<?php

namespace App\Models\Ot;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OtImplant extends Model
{
    protected $fillable = [
        'surgery_id', 'implant_name', 'manufacturer', 'model', 'serial_number', 'lot_batch',
        'expiry_date', 'quantity', 'implanted_at', 'implanted_by', 'billing_charge_id',
    ];

    protected function casts(): array
    {
        return ['expiry_date' => 'date', 'implanted_at' => 'datetime'];
    }

    public function surgery(): BelongsTo
    {
        return $this->belongsTo(OtSurgery::class, 'surgery_id');
    }

    public function implantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'implanted_by');
    }
}
