<?php

namespace Tests\Feature\Pharmacy;

use App\Models\Organization;
use App\Services\Pharmacy\PharmacyNumberGenerator;

/**
 * Mirrors RadiologyNumberGeneratorConcurrencyTest's two-PDO-connection technique for
 * PharmacyNumberGenerator (used for pharmacy_orders.order_number and dispensing/transfer/return numbers).
 */
class PharmacyNumberGeneratorConcurrencyTest extends PharmacyTestCase
{
    public function test_generator_never_produces_duplicate_sequence_numbers_under_repeated_calls(): void
    {
        $organization = Organization::factory()->create();

        $generator = app(PharmacyNumberGenerator::class);

        $numbers = [];
        for ($i = 0; $i < 10; $i++) {
            $numbers[] = $generator->generateOrderNumber($organization->id, null);
        }

        $this->assertCount(10, array_unique($numbers), 'Every generated order number must be unique.');
    }

    public function test_row_lock_on_the_counter_blocks_a_concurrent_connection(): void
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

        $organizationId = (int) $pdoA->query('SELECT id FROM organizations LIMIT 1')->fetchColumn();

        if (! $organizationId) {
            $pdoA->exec("INSERT INTO organizations (name, code, slug, created_at, updated_at) VALUES ('Concurrency Test Co', 'CTC', 'concurrency-test-co', NOW(), NOW())");
            $organizationId = (int) $pdoA->lastInsertId();
        }

        $pdoA->exec("INSERT INTO pharmacy_counters (organization_id, branch_id, document_type, prefix, last_number, created_at, updated_at)
            VALUES ({$organizationId}, NULL, 'ORD', 'RXC', 0, NOW(), NOW())");
        $counterId = (int) $pdoA->lastInsertId();

        try {
            $pdoB->exec('SET SESSION innodb_lock_wait_timeout = 1');

            $pdoA->beginTransaction();
            $pdoA->query("SELECT last_number FROM pharmacy_counters WHERE id = {$counterId} FOR UPDATE")->fetch();

            $blocked = false;

            try {
                $pdoB->beginTransaction();
                $pdoB->query("SELECT last_number FROM pharmacy_counters WHERE id = {$counterId} FOR UPDATE")->fetch();
                $pdoB->rollBack();
            } catch (\PDOException $e) {
                $blocked = str_contains($e->getMessage(), 'Lock wait timeout exceeded');
                if ($pdoB->inTransaction()) {
                    $pdoB->rollBack();
                }
            }

            $this->assertTrue($blocked, 'A concurrent connection must be blocked from locking the same counter row while a generation transaction is in flight.');

            $pdoA->exec("UPDATE pharmacy_counters SET last_number = last_number + 1 WHERE id = {$counterId}");
            $pdoA->commit();

            $pdoB->beginTransaction();
            $lastNumber = $pdoB->query("SELECT last_number FROM pharmacy_counters WHERE id = {$counterId} FOR UPDATE")->fetchColumn();
            $pdoB->rollBack();

            $this->assertSame(1, (int) $lastNumber);
        } finally {
            if ($pdoA->inTransaction()) {
                $pdoA->rollBack();
            }
            $pdoA->exec("DELETE FROM pharmacy_counters WHERE id = {$counterId}");
        }
    }
}
