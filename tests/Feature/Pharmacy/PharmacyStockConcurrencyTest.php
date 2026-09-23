<?php

namespace Tests\Feature\Pharmacy;

/**
 * The mandatory "two pharmacists, same batch, concurrent dispensing" test (Phase 7 plan §10).
 * Mirrors RadiologyNumberGeneratorConcurrencyTest's raw two-PDO-connection technique, applied to
 * PharmacyStockService's lockForUpdate() on the pharmacy_stock row: proves a concurrent
 * connection is blocked from touching the same stock row while a debit transaction is in flight,
 * and that stock never goes negative under real MySQL locking (not mocked).
 *
 * The raw-PDO test below connects directly to the live MySQL database (not the RefreshDatabase
 * sqlite :memory: test connection) and creates/cleans up its own fixture rows via SQL, exactly
 * like RadiologyNumberGeneratorConcurrencyTest — Eloquent fixtures built in setUp() live on a
 * different connection and would not be visible to the raw PDO connections here.
 */
class PharmacyStockConcurrencyTest extends PharmacyTestCase
{
    public function test_row_lock_on_stock_blocks_a_concurrent_connection_and_prevents_negative_stock(): void
    {
        $host = getenv('MYSQL_TEST_HOST') ?: 'localhost';
        $port = getenv('MYSQL_TEST_PORT') ?: '3306';
        $database = getenv('MYSQL_TEST_DATABASE') ?: 'healthnexus';
        $username = getenv('MYSQL_TEST_USERNAME') ?: 'root';
        $password = getenv('MYSQL_TEST_PASSWORD') ?: 'root';

        try {
            $dsn = "mysql:host={$host};port={$port};dbname={$database}";
            $pdoA = new \PDO($dsn, $username, $password);
            $pdoB = new \PDO($dsn, $username, $password);
        } catch (\PDOException $e) {
            $this->markTestSkipped('No local MySQL server reachable for the raw-connection lock test: '.$e->getMessage());

            return;
        }

        $pdoA->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdoB->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        $companyId = (int) $pdoA->query('SELECT id FROM companies LIMIT 1')->fetchColumn();

        if (! $companyId) {
            $pdoA->exec("INSERT INTO companies (name, code, created_at, updated_at) VALUES ('Concurrency Test Co', 'CTC', NOW(), NOW())");
            $companyId = (int) $pdoA->lastInsertId();
        }

        $pdoA->exec("INSERT INTO pharmacy_generics (company_id, code, generic_name, is_active, created_at, updated_at)
            VALUES ({$companyId}, 'GEN-CONC', 'Concurrency Generic', 1, NOW(), NOW())");
        $genericId = (int) $pdoA->lastInsertId();

        $pdoA->exec("INSERT INTO pharmacy_medications (company_id, generic_id, code, name, is_active, created_at, updated_at)
            VALUES ({$companyId}, {$genericId}, 'MED-CONC', 'Concurrency Medication', 1, NOW(), NOW())");
        $medicationId = (int) $pdoA->lastInsertId();

        $pdoA->exec("INSERT INTO pharmacy_stores (company_id, code, name, store_type, is_active, created_at, updated_at)
            VALUES ({$companyId}, 'STORE-CONC', 'Concurrency Store', 'main', 1, NOW(), NOW())");
        $storeId = (int) $pdoA->lastInsertId();

        $expiry = now()->addYear()->toDateString();
        $pdoA->exec("INSERT INTO pharmacy_batches (company_id, medication_id, batch_number, expiry_date, is_quarantined, created_at, updated_at)
            VALUES ({$companyId}, {$medicationId}, 'BATCH-CONC', '{$expiry}', 0, NOW(), NOW())");
        $batchId = (int) $pdoA->lastInsertId();

        $pdoA->exec("INSERT INTO pharmacy_stock (store_id, medication_id, batch_id, quantity_available, created_at, updated_at)
            VALUES ({$storeId}, {$medicationId}, {$batchId}, 10, NOW(), NOW())");
        $stockId = (int) $pdoA->lastInsertId();

        try {
            $pdoB->exec('SET SESSION innodb_lock_wait_timeout = 1');

            $pdoA->beginTransaction();
            $pdoA->query("SELECT quantity_available FROM pharmacy_stock WHERE id = {$stockId} FOR UPDATE")->fetch();

            $blocked = false;

            try {
                $pdoB->beginTransaction();
                $pdoB->query("SELECT quantity_available FROM pharmacy_stock WHERE id = {$stockId} FOR UPDATE")->fetch();
                $pdoB->rollBack();
            } catch (\PDOException $e) {
                $blocked = str_contains($e->getMessage(), 'Lock wait timeout exceeded');
                if ($pdoB->inTransaction()) {
                    $pdoB->rollBack();
                }
            }

            $this->assertTrue($blocked, 'A concurrent connection must be blocked from locking the same pharmacy_stock row while a debit transaction is in flight.');

            $pdoA->exec("UPDATE pharmacy_stock SET quantity_available = quantity_available - 10 WHERE id = {$stockId}");
            $pdoA->commit();

            $pdoB->beginTransaction();
            $remaining = $pdoB->query("SELECT quantity_available FROM pharmacy_stock WHERE id = {$stockId} FOR UPDATE")->fetchColumn();
            $pdoB->rollBack();

            $this->assertSame(0, (int) $remaining);
        } finally {
            if ($pdoA->inTransaction()) {
                $pdoA->rollBack();
            }
            $pdoA->exec("DELETE FROM pharmacy_stock WHERE id = {$stockId}");
            $pdoA->exec("DELETE FROM pharmacy_batches WHERE id = {$batchId}");
            $pdoA->exec("DELETE FROM pharmacy_stores WHERE id = {$storeId}");
            $pdoA->exec("DELETE FROM pharmacy_medications WHERE id = {$medicationId}");
            $pdoA->exec("DELETE FROM pharmacy_generics WHERE id = {$genericId}");
        }
    }

    public function test_debit_refuses_to_take_stock_below_zero(): void
    {
        $medication = $this->makeMedication();
        $store = $this->makeStore();
        $this->receiveBatch($store, $medication, 5);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        app(\App\Services\Pharmacy\PharmacyStockService::class)->debit(
            $store, $medication, \App\Models\Pharmacy\PharmacyBatch::where('medication_id', $medication->id)->first(),
            10, 'dispensing', $this->user,
        );
    }
}
