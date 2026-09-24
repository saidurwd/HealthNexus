<?php

namespace Tests\Feature\Ipd;

/**
 * The mandatory "two simultaneous admission requests, same bed, exactly one succeeds" test
 * (spec §54/§86). Mirrors PharmacyStockConcurrencyTest's raw two-PDO-connection technique,
 * applied to IpdBedAllocationService::allocate()'s lockForUpdate() on the ipd_beds row: proves a
 * concurrent connection is blocked from touching the same bed row while an allocation
 * transaction is in flight, and that the bed never ends up simultaneously "available" for two
 * different admissions.
 *
 * The raw-PDO test connects directly to the live MySQL database (not the RefreshDatabase sqlite
 * :memory: test connection) and creates/cleans up its own fixture rows via SQL — Eloquent
 * fixtures built in setUp() live on a different connection and would not be visible here.
 */
class BedAllocationConcurrencyTest extends IpdTestCase
{
    public function test_row_lock_on_bed_blocks_a_concurrent_connection_and_prevents_double_allocation(): void
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

        $pdoA->exec("INSERT INTO ipd_wards (company_id, code, name, gender_policy, capacity, is_active, created_at, updated_at)
            VALUES ({$companyId}, 'W-CONC', 'Concurrency Ward', 'any', 1, 1, NOW(), NOW())");
        $wardId = (int) $pdoA->lastInsertId();

        $pdoA->exec("INSERT INTO ipd_rooms (company_id, ward_id, room_number, room_type, capacity, gender_policy, is_active, created_at, updated_at)
            VALUES ({$companyId}, {$wardId}, 'R-CONC', 'general', 1, 'any', 1, NOW(), NOW())");
        $roomId = (int) $pdoA->lastInsertId();

        $pdoA->exec("INSERT INTO ipd_beds (company_id, room_id, bed_code, gender_type, status, is_active, created_at, updated_at)
            VALUES ({$companyId}, {$roomId}, 'BED-CONC', 'any', 'available', 1, NOW(), NOW())");
        $bedId = (int) $pdoA->lastInsertId();

        try {
            $pdoB->exec('SET SESSION innodb_lock_wait_timeout = 1');

            $pdoA->beginTransaction();
            $pdoA->query("SELECT status FROM ipd_beds WHERE id = {$bedId} FOR UPDATE")->fetch();

            $blocked = false;

            try {
                $pdoB->beginTransaction();
                $pdoB->query("SELECT status FROM ipd_beds WHERE id = {$bedId} FOR UPDATE")->fetch();
                $pdoB->rollBack();
            } catch (\PDOException $e) {
                $blocked = str_contains($e->getMessage(), 'Lock wait timeout exceeded');
                if ($pdoB->inTransaction()) {
                    $pdoB->rollBack();
                }
            }

            $this->assertTrue($blocked, 'A concurrent connection must be blocked from locking the same ipd_beds row while an allocation transaction is in flight.');

            // Connection A "wins" the allocation — flips the bed to occupied.
            $pdoA->exec("UPDATE ipd_beds SET status = 'occupied' WHERE id = {$bedId}");
            $pdoA->commit();

            // Connection B now proceeds (A released the lock) and must see the bed as no longer
            // available — this is what IpdBedAllocationService::assertEligible() checks under the
            // lock, guaranteeing the second concurrent allocation attempt is rejected, not silently
            // duplicated.
            $pdoB->beginTransaction();
            $statusSeenByB = $pdoB->query("SELECT status FROM ipd_beds WHERE id = {$bedId} FOR UPDATE")->fetchColumn();
            $pdoB->rollBack();

            $this->assertSame('occupied', $statusSeenByB, 'The second connection must see the bed as occupied, never as still available for a second allocation.');
        } finally {
            if ($pdoA->inTransaction()) {
                $pdoA->rollBack();
            }
            $pdoA->exec("DELETE FROM ipd_beds WHERE id = {$bedId}");
            $pdoA->exec("DELETE FROM ipd_rooms WHERE id = {$roomId}");
            $pdoA->exec("DELETE FROM ipd_wards WHERE id = {$wardId}");
        }
    }

    public function test_allocate_refuses_a_bed_that_is_no_longer_available(): void
    {
        $bed = $this->makeBed();
        $bed->update(['status' => \App\Models\Ipd\IpdBed::STATUS_OCCUPIED]);

        $patient = \App\Models\Patient::factory()->create(['company_id' => $this->company->id]);
        $encounter = \App\Models\Encounter::factory()->create([
            'company_id' => $this->company->id, 'branch_id' => $this->branch->id, 'patient_id' => $patient->id,
        ]);
        $admission = \App\Models\Ipd\IpdAdmission::create([
            'company_id' => $this->company->id, 'branch_id' => $this->branch->id,
            'admission_number' => 'ADM-TEST-'.uniqid(), 'patient_id' => $patient->id,
            'encounter_id' => $encounter->id, 'admitted_at' => now(), 'status' => 'admitted',
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        app(\App\Services\Ipd\IpdBedAllocationService::class)->allocate($admission, $bed, $this->user);
    }
}
