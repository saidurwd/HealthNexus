<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProcurementApproval extends Model
{
    use HasFactory;

    protected $table = 'procurement_approvals';

    protected $guarded = ['id'];

    protected $casts = ['decided_at' => 'datetime'];
}
