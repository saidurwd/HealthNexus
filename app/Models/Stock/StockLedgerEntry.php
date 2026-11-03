<?php

namespace App\Models\Stock;

use Illuminate\Database\Eloquent\Model;
use LogicException;

/** One row of the append-only stock ledger. The database also refuses UPDATE/DELETE (triggers). */
class StockLedgerEntry extends Model
{
    protected $table = 'stock_ledger';

    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = ['quantity' => 'float', 'unit_cost' => 'float', 'performed_at' => 'datetime', 'created_at' => 'datetime'];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('stock_ledger is append-only; post a correcting entry instead.'));
        static::deleting(fn () => throw new LogicException('stock_ledger is append-only; post a correcting entry instead.'));
    }
}
