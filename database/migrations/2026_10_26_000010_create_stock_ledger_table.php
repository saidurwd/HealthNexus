<?php

use App\Services\Stock\PharmacyLedgerMapper;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Stock Ledger (guideline 12.2, H-06). Append-only: rows are never updated or deleted, corrections are new rows.
 * The database itself refuses UPDATE and DELETE (triggers), so a bug or a hand-run SQL statement cannot rewrite history.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_ledger', function (Blueprint $table) {
            $table->id();
            // No cascading/nulling foreign keys: those actions would be UPDATE/DELETEs on an append-only table.
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('store_kind', 12);                       // pharmacy, inventory (Phase 15)
            $table->unsignedBigInteger('store_id');
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('batch_id')->nullable();
            $table->unsignedBigInteger('serial_id')->nullable();
            $table->decimal('quantity', 18, 4);                      // signed: + in, - out
            $table->decimal('unit_cost', 18, 4)->nullable();
            $table->string('txn_type', 24);
            $table->string('reference_type', 100)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('reason')->nullable();
            $table->unsignedBigInteger('performed_by')->nullable();
            $table->timestamp('performed_at');
            $table->string('idempotency_key', 120)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['organization_id', 'idempotency_key'], 'uq_stock_ledger_key');
            $table->index(['store_kind', 'store_id', 'item_id', 'batch_id'], 'idx_stock_ledger_position');
            $table->index(['reference_type', 'reference_id'], 'idx_stock_ledger_reference');
        });

        $this->backfillPharmacy();
        $this->protect();

        DB::statement('CREATE VIEW v_stock_on_hand_all_stores AS
            SELECT organization_id, store_kind, store_id, item_id, batch_id, SUM(quantity) AS on_hand
            FROM stock_ledger GROUP BY organization_id, store_kind, store_id, item_id, batch_id');
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS v_stock_on_hand_all_stores');
        DB::unprepared('DROP TRIGGER IF EXISTS stock_ledger_no_update');
        DB::unprepared('DROP TRIGGER IF EXISTS stock_ledger_no_delete');
        Schema::dropIfExists('stock_ledger');
    }

    /** Copies pharmacy's existing movement history, then adds an opening row wherever history and on-hand disagree. */
    private function backfillPharmacy(): void
    {
        if (! Schema::hasTable('pharmacy_stock_transactions')) {
            return;
        }

        DB::table('pharmacy_stock_transactions as t')
            ->join('pharmacy_stores as s', 's.id', '=', 't.store_id')
            ->select('t.*', 's.organization_id', 's.branch_id as store_branch_id')
            ->orderBy('t.id')
            ->chunkById(1000, function ($rows) {
                DB::table('stock_ledger')->insert($rows->map(fn ($r) => [
                    'organization_id' => $r->organization_id, 'branch_id' => $r->store_branch_id, 'store_kind' => 'pharmacy',
                    'store_id' => $r->store_id, 'item_id' => $r->medication_id, 'batch_id' => $r->batch_id,
                    'quantity' => $r->direction === 'in' ? $r->quantity : -$r->quantity,
                    'txn_type' => PharmacyLedgerMapper::txnType($r->type, $r->direction),
                    'reference_type' => $r->reference_type, 'reference_id' => $r->reference_id, 'reason' => $r->reason,
                    'performed_by' => $r->performed_by, 'performed_at' => $r->created_at ?? now(),
                    'idempotency_key' => 'PHM-TXN:'.$r->id,
                ])->all());
            }, 't.id', 'id');

        DB::table('pharmacy_stock as p')
            ->join('pharmacy_stores as s', 's.id', '=', 'p.store_id')
            ->leftJoin(DB::raw('(SELECT store_id, item_id, batch_id, SUM(quantity) AS q FROM stock_ledger WHERE store_kind = \'pharmacy\' GROUP BY store_id, item_id, batch_id) as l'),
                fn ($j) => $j->on('l.store_id', '=', 'p.store_id')->on('l.item_id', '=', 'p.medication_id')->on('l.batch_id', '=', 'p.batch_id'))
            ->select('p.*', 's.organization_id', 's.branch_id as store_branch_id', DB::raw('COALESCE(l.q, 0) as ledger_qty'))
            ->orderBy('p.id')
            ->each(function ($r) {
                $diff = $r->quantity_available - $r->ledger_qty;
                if ((float) $diff !== 0.0) {
                    DB::table('stock_ledger')->insert([
                        'organization_id' => $r->organization_id, 'branch_id' => $r->store_branch_id, 'store_kind' => 'pharmacy',
                        'store_id' => $r->store_id, 'item_id' => $r->medication_id, 'batch_id' => $r->batch_id,
                        'quantity' => $diff, 'txn_type' => 'opening_balance', 'reason' => 'Opening balance carried into the ledger (H-06)',
                        'performed_at' => now(), 'idempotency_key' => 'PHM-OPEN:'.$r->id,
                    ]);
                }
            });
    }

    private function protect(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::unprepared("CREATE TRIGGER stock_ledger_no_update BEFORE UPDATE ON stock_ledger BEGIN SELECT RAISE(ABORT, 'stock_ledger is append-only'); END");
            DB::unprepared("CREATE TRIGGER stock_ledger_no_delete BEFORE DELETE ON stock_ledger BEGIN SELECT RAISE(ABORT, 'stock_ledger is append-only'); END");

            return;
        }

        DB::unprepared("CREATE TRIGGER stock_ledger_no_update BEFORE UPDATE ON stock_ledger FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'stock_ledger is append-only'");
        DB::unprepared("CREATE TRIGGER stock_ledger_no_delete BEFORE DELETE ON stock_ledger FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'stock_ledger is append-only'");
    }
};
