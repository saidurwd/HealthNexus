<?php

namespace App\Models\Insurance;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CorporateBatch extends Model
{
    use HasFactory, HasPublicId;

    protected $table = 'corporate_batches';

    protected $guarded = ['id', 'public_id'];

    protected $casts = ['period_from' => 'date', 'period_to' => 'date', 'due_on' => 'date', 'issued_at' => 'datetime', 'total_amount' => 'decimal:2', 'paid_amount' => 'decimal:2'];
}
