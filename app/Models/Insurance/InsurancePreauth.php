<?php

namespace App\Models\Insurance;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InsurancePreauth extends Model
{
    use HasFactory, HasPublicId;

    protected $guarded = ['id', 'public_id'];

    protected $casts = ['requested_amount' => 'decimal:2', 'authorized_amount' => 'decimal:2', 'consumed_amount' => 'decimal:2', 'valid_from' => 'date', 'valid_to' => 'date', 'requested_at' => 'datetime', 'decided_at' => 'datetime'];

    public function remaining(): string
    {
        return bcsub((string) $this->authorized_amount, (string) $this->consumed_amount, 2);
    }
}
