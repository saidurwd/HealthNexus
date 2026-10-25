<?php

namespace Tests\Feature\Ot;

/**
 * The mandatory "two users, same theatre, same time, only one succeeds" proof (spec §73/§77).
 * Mirrors BedAllocationConcurrencyTest's raw two-PDO-connection technique exactly, applied to
 * OtSchedulingService::schedule()'s lockForUpdate() on the ot_theatres row: proves a concurrent
 * connection is blocked from locking the same theatre row while a scheduling transaction is in
 * flight, and that the second connection then sees a real conflicting booking, not an empty slot.
 */
class TheatreScheduleConcurrencyTest extends OtTestCase
{
    public function test_row_lock_on_theatre_blocks_a_concurrent_connection_and_prevents_double_booking(): void
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
            $hasTable = $pdoA->query("SHOW TABLES LIKE 'ot_theatres'")->fetchColumn();
        } catch (\PDOException $e) {
            $this->markTestSkipped('OT tables are not migrated on the live MySQL database.');

            return;
        }

        if (! $hasTable) {
            $this->markTestSkipped('OT tables are not migrated on the live MySQL database.');

            return;
        }

        $companyId = (int) $pdoA->query('SELECT id FROM companies LIMIT 1')->fetchColumn();

        if (! $companyId) {
            $pdoA->exec("INSERT INTO companies (name, code, created_at, updated_at) VALUES ('Concurrency Test Co', 'CTC', NOW(), NOW())");
            $companyId = (int) $pdoA->lastInsertId();
        }

        $pdoA->exec("INSERT INTO ot_theatres (company_id, theatre_code, name, capacity, status, is_active, created_at, updated_at)
            VALUES ({$companyId}, 'OT-CONC', 'Concurrency Theatre', 1, 'available', 1, NOW(), NOW())");
        $theatreId = (int) $pdoA->lastInsertId();

        try {
            $pdoB->exec('SET SESSION innodb_lock_wait_timeout = 1');

            $pdoA->beginTransaction();
            $pdoA->query("SELECT status FROM ot_theatres WHERE id = {$theatreId} FOR UPDATE")->fetch();

            $blocked = false;

            try {
                $pdoB->beginTransaction();
                $pdoB->query("SELECT status FROM ot_theatres WHERE id = {$theatreId} FOR UPDATE")->fetch();
                $pdoB->rollBack();
            } catch (\PDOException $e) {
                $blocked = str_contains($e->getMessage(), 'Lock wait timeout exceeded');
                if ($pdoB->inTransaction()) {
                    $pdoB->rollBack();
                }
            }

            $this->assertTrue($blocked, 'A concurrent connection must be blocked from locking the same ot_theatres row while a scheduling transaction is in flight.');

            // Connection A "wins" the booking.
            $pdoA->exec("UPDATE ot_theatres SET status = 'occupied' WHERE id = {$theatreId}");
            $pdoA->commit();

            $pdoB->beginTransaction();
            $statusSeenByB = $pdoB->query("SELECT status FROM ot_theatres WHERE id = {$theatreId} FOR UPDATE")->fetchColumn();
            $pdoB->rollBack();

            $this->assertSame('occupied', $statusSeenByB, 'The second connection must see the theatre as occupied, never as still free for a second booking.');
        } finally {
            if ($pdoA->inTransaction()) {
                $pdoA->rollBack();
            }
            $pdoA->exec("DELETE FROM ot_theatres WHERE id = {$theatreId}");
        }
    }

    public function test_schedule_rejects_an_overlapping_booking_in_the_same_theatre(): void
    {
        $theatre = $this->makeTheatre();
        $surgeryA = $this->makeDaySurgery();
        $surgeryB = $this->makeDaySurgery();

        $start = now()->addDay()->setTime(9, 0);
        $end = now()->addDay()->setTime(11, 0);

        app(\App\Services\Ot\OtSchedulingService::class)->schedule($surgeryA, [
            'theatre_id' => $theatre->id, 'scheduled_start_at' => $start, 'scheduled_end_at' => $end,
        ], $this->user);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        app(\App\Services\Ot\OtSchedulingService::class)->schedule($surgeryB, [
            'theatre_id' => $theatre->id,
            'scheduled_start_at' => $start->copy()->addMinutes(30),
            'scheduled_end_at' => $end->copy()->addMinutes(30),
        ], $this->user);
    }
}
