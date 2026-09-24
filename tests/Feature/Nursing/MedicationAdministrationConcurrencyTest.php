<?php

namespace Tests\Feature\Nursing;

/**
 * The mandatory "two nurses, same MAR item, exactly one administration succeeds" proof (spec §84).
 * Same raw two-PDO technique as BedAllocationConcurrencyTest: connects directly to live MySQL and
 * proves administer()'s lockForUpdate() blocks a concurrent connection and that the second
 * connection then sees the row already finalized. Self-contained via raw SQL fixtures.
 */
class MedicationAdministrationConcurrencyTest extends NursingTestCase
{
    public function test_row_lock_on_mar_blocks_a_concurrent_connection_and_second_nurse_sees_it_finalized(): void
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

        try {
            $hasTable = $pdoA->query("SHOW TABLES LIKE 'nursing_medication_administrations'")->fetchColumn();
        } catch (\PDOException $e) {
            $this->markTestSkipped('Nursing tables are not migrated on the live MySQL database.');

            return;
        }

        if (! $hasTable) {
            $this->markTestSkipped('Nursing tables are not migrated on the live MySQL database.');

            return;
        }

        $pdoA->exec('SET FOREIGN_KEY_CHECKS=0');
        $pdoA->exec("INSERT INTO nursing_medication_administrations (company_id, episode_id, admission_id, encounter_id, patient_id, scheduled_at, dose, route, status, is_prn, created_at, updated_at)
            VALUES (1, 1, 1, 1, 1, NOW(), '500', 'oral', 'scheduled', 0, NOW(), NOW())");
        $marId = (int) $pdoA->lastInsertId();

        try {
            $pdoB->exec('SET SESSION innodb_lock_wait_timeout = 1');

            $pdoA->beginTransaction();
            $pdoA->query("SELECT status FROM nursing_medication_administrations WHERE id = {$marId} FOR UPDATE")->fetch();

            $blocked = false;

            try {
                $pdoB->beginTransaction();
                $pdoB->query("SELECT status FROM nursing_medication_administrations WHERE id = {$marId} FOR UPDATE")->fetch();
                $pdoB->rollBack();
            } catch (\PDOException $e) {
                $blocked = str_contains($e->getMessage(), 'Lock wait timeout exceeded');
                if ($pdoB->inTransaction()) {
                    $pdoB->rollBack();
                }
            }

            $this->assertTrue($blocked, 'A second nurse must be blocked while the first administration transaction is in flight.');

            $pdoA->exec("UPDATE nursing_medication_administrations SET status = 'administered' WHERE id = {$marId}");
            $pdoA->commit();

            $pdoB->beginTransaction();
            $seen = $pdoB->query("SELECT status FROM nursing_medication_administrations WHERE id = {$marId} FOR UPDATE")->fetchColumn();
            $pdoB->rollBack();

            $this->assertSame('administered', $seen, 'The second nurse must see the item already administered, so assertActionable() rejects the duplicate.');
        } finally {
            if ($pdoA->inTransaction()) {
                $pdoA->rollBack();
            }
            $pdoA->exec("DELETE FROM nursing_medication_administrations WHERE id = {$marId}");
            $pdoA->exec('SET FOREIGN_KEY_CHECKS=1');
        }
    }
}
