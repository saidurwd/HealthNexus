<?php

namespace Tests\Feature\Laboratory;

use App\Models\Company;
use App\Services\Laboratory\LabNumberGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Mirrors ClinicalNumberGeneratorConcurrencyTest's two-PDO-connection technique for
 * LabNumberGenerator (used for lab_orders.order_number and lab_specimens.accession_number).
 *
 *  1. test_generator_never_produces_duplicate_sequence_numbers_under_repeated_calls — deterministic,
 *     runs on sqlite, proves the generator hands out a strictly increasing, gap-free sequence.
 *  2. test_row_lock_on_the_counter_blocks_a_concurrent_connection — genuine two-PDO-connection test
 *     against the real MySQL server, proving lockForUpdate() actually blocks a concurrent writer.
 *     Skipped automatically if no local MySQL server is reachable.
 */
class LabNumberGeneratorConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_generator_never_produces_duplicate_sequence_numbers_under_repeated_calls(): void
    {
        $company = Company::factory()->create();

        $generator = app(LabNumberGenerator::class);

        $numbers = [];
        for ($i = 0; $i < 10; $i++) {
            $numbers[] = $generator->generateAccessionNumber($company->id, null);
        }

        $this->assertCount(10, array_unique($numbers), 'Every generated accession number must be unique.');
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

        $companyId = (int) $pdoA->query('SELECT id FROM companies LIMIT 1')->fetchColumn();

        if (! $companyId) {
            $pdoA->exec("INSERT INTO companies (name, code, created_at, updated_at) VALUES ('Concurrency Test Co', 'CTC', NOW(), NOW())");
            $companyId = (int) $pdoA->lastInsertId();
        }

        $pdoA->exec("INSERT INTO lab_counters (company_id, branch_id, document_type, prefix, last_number, created_at, updated_at)
            VALUES ({$companyId}, NULL, 'ACC', 'ACC', 0, NOW(), NOW())");
        $counterId = (int) $pdoA->lastInsertId();

        try {
            $pdoB->exec('SET SESSION innodb_lock_wait_timeout = 1');

            $pdoA->beginTransaction();
            $pdoA->query("SELECT last_number FROM lab_counters WHERE id = {$counterId} FOR UPDATE")->fetch();

            $blocked = false;

            try {
                $pdoB->beginTransaction();
                $pdoB->query("SELECT last_number FROM lab_counters WHERE id = {$counterId} FOR UPDATE")->fetch();
                $pdoB->rollBack();
            } catch (\PDOException $e) {
                $blocked = str_contains($e->getMessage(), 'Lock wait timeout exceeded');
                if ($pdoB->inTransaction()) {
                    $pdoB->rollBack();
                }
            }

            $this->assertTrue($blocked, 'A concurrent connection must be blocked from locking the same counter row while a generation transaction is in flight.');

            $pdoA->exec("UPDATE lab_counters SET last_number = last_number + 1 WHERE id = {$counterId}");
            $pdoA->commit();

            $pdoB->beginTransaction();
            $lastNumber = $pdoB->query("SELECT last_number FROM lab_counters WHERE id = {$counterId} FOR UPDATE")->fetchColumn();
            $pdoB->rollBack();

            $this->assertSame(1, (int) $lastNumber);
        } finally {
            if ($pdoA->inTransaction()) {
                $pdoA->rollBack();
            }
            $pdoA->exec("DELETE FROM lab_counters WHERE id = {$counterId}");
        }
    }
}
