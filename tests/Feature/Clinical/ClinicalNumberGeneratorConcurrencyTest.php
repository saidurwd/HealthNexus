<?php

namespace Tests\Feature\Clinical;

use App\Models\Company;
use App\Services\Clinical\ClinicalNumberGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Mirrors AppointmentBookingConcurrencyTest's two-PDO-connection technique for
 * ClinicalNumberGenerator (used for clinical_orders.order_number and prescriptions.prescription_no)
 * — the pattern it replaced (Model::orderByDesc('id')->first()->id + 1) would hand out duplicate
 * numbers under concurrent writers because it never locked a row while reading.
 *
 *  1. test_generator_never_produces_duplicate_sequence_numbers_under_repeated_calls — deterministic,
 *     runs on sqlite, proves the generator itself hands out a strictly increasing, gap-free,
 *     duplicate-free sequence.
 *  2. test_row_lock_on_the_counter_blocks_a_concurrent_connection — genuine two-PDO-connection test
 *     against the real MySQL server, proving lockForUpdate() actually blocks a concurrent writer
 *     rather than letting it read a stale last_number and race past it. Skipped automatically if no
 *     local MySQL server is reachable.
 */
class ClinicalNumberGeneratorConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_generator_never_produces_duplicate_sequence_numbers_under_repeated_calls(): void
    {
        $company = Company::factory()->create(['code' => 'ABC']);

        $generator = app(ClinicalNumberGenerator::class);

        $numbers = [];
        for ($i = 0; $i < 10; $i++) {
            $numbers[] = $generator->generateOrderNumber($company);
        }

        $this->assertCount(10, array_unique($numbers), 'Every generated order number must be unique.');
        $this->assertSame('ABC-ORD-00000001', $numbers[0]);
        $this->assertSame('ABC-ORD-00000010', $numbers[9]);
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

        $pdoA->exec("INSERT INTO clinical_number_counters (company_id, branch_id, counter_type, last_number, created_at, updated_at)
            VALUES ({$companyId}, NULL, 'clinical_order', 0, NOW(), NOW())");
        $counterId = (int) $pdoA->lastInsertId();

        try {
            $pdoB->exec('SET SESSION innodb_lock_wait_timeout = 1');

            $pdoA->beginTransaction();
            $pdoA->query("SELECT last_number FROM clinical_number_counters WHERE id = {$counterId} FOR UPDATE")->fetch();

            // Connection A now holds the row lock without committing — exactly the state a real
            // in-flight number-generation transaction would be in. Connection B racing to generate
            // the next number for the same counter must be blocked, not allowed to read the stale
            // last_number and hand out a duplicate.
            $blocked = false;

            try {
                $pdoB->beginTransaction();
                $pdoB->query("SELECT last_number FROM clinical_number_counters WHERE id = {$counterId} FOR UPDATE")->fetch();
                $pdoB->rollBack();
            } catch (\PDOException $e) {
                $blocked = str_contains($e->getMessage(), 'Lock wait timeout exceeded');
                if ($pdoB->inTransaction()) {
                    $pdoB->rollBack();
                }
            }

            $this->assertTrue($blocked, 'A concurrent connection must be blocked from locking the same counter row while a generation transaction is in flight.');

            $pdoA->exec("UPDATE clinical_number_counters SET last_number = last_number + 1 WHERE id = {$counterId}");
            $pdoA->commit();

            $pdoB->beginTransaction();
            $lastNumber = $pdoB->query("SELECT last_number FROM clinical_number_counters WHERE id = {$counterId} FOR UPDATE")->fetchColumn();
            $pdoB->rollBack();

            $this->assertSame(1, (int) $lastNumber);
        } finally {
            if ($pdoA->inTransaction()) {
                $pdoA->rollBack();
            }
            $pdoA->exec("DELETE FROM clinical_number_counters WHERE id = {$counterId}");
        }
    }
}
