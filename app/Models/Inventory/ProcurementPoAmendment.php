<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProcurementPoAmendment extends Model
{
    use HasFactory;

    protected $table = 'procurement_po_amendments';

    protected $guarded = ['id'];

    protected $casts = ['snapshot' => 'array'];
}
