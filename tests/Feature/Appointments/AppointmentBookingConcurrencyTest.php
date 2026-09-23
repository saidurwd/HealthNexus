<?php

namespace Tests\Feature\Appointments;

use App\Models\Appointment;
use App\Models\AppointmentSlot;
use App\Models\Branch;
use App\Models\Company;
use App\Models\DoctorSchedule;
use App\Models\Patient;
use App\Models\User;
use App\Services\Appointments\AppointmentBookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Mandatory concurrency coverage (spec: "Two receptionists may attempt to book Dr. Ahmed 10:00
 * at exactly the same time. The system must guarantee that capacity is not exceeded... Test this
 * explicitly."). The previous booking code (AppointmentService::createAppointment()) had no
 * capacity check at all before inserting — this class proves the fix two ways:
 *
 *  1. test_booking_service_never_exceeds_slot_capacity_under_repeated_calls — deterministic,
 *     runs on the default sqlite test connection like every other feature test in this suite:
 *     proves AppointmentBookingService itself enforces capacity and rejects overflow.
 *
 *  2. test_row_lock_on_the_slot_blocks_a_concurrent_connection — a genuine two-PDO-connection
 *     test against the real MySQL server (sqlite is single-connection and can't demonstrate
 *     cross-connection blocking; a true multi-process test would need OS-level forking, which
 *     isn't practical in a portable PHPUnit suite). It opens a second, independent MySQL
 *     connection, holds SELECT ... FOR UPDATE open on connection A without committing, and
 *     proves connection B's attempt to lock the same row blocks (times out) rather than reading
 *     stale data and racing past it — the exact mechanism AppointmentBookingService relies on.
 *     Skipped automatically if no local MySQL server is reachable.
 */
class AppointmentBookingConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_booking_service_never_exceeds_slot_capacity_under_repeated_calls(): void
    {
        $company = Company::factory()->create();
        $branch = Branch::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create();
        $user->companies()->attach($company->id, ['access_level' => 'admin']);

        $doctor = User::factory()->create();
        $schedule = DoctorSchedule::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'doctor_id' => $doctor->id,
        ]);

        $slot = AppointmentSlot::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'doctor_id' => $doctor->id,
            'schedule_id' => $schedule->id,
            'max_capacity' => 2,
            'booked_count' => 0,
            'status' => 'available',
        ]);

        $patients = Patient::factory()->count(3)->create(['company_id' => $company->id]);

        $service = app(AppointmentBookingService::class);
        $booked = 0;
        $rejected = 0;

        foreach ($patients as $patient) {
            try {
                $service->book([
                    'company_id' => $company->id,
                    'branch_id' => $branch->id,
                    'patient_id' => $patient->id,
                    'doctor_id' => $doctor->id,
                    'slot_id' => $slot->id,
                    'type' => 'scheduled',
                    'source' => 'offline',
                ], $user);
                $booked++;
            } catch (ValidationException) {
                $rejected++;
            }
        }

        $this->assertSame(2, $booked, 'Exactly max_capacity appointments should succeed.');
        $this->assertSame(1, $rejected, 'Any booking beyond capacity must be rejected, not silently accepted.');
        $this->assertSame(2, $slot->fresh()->booked_count);
        $this->assertSame('booked', $slot->fresh()->status);
        $this->assertSame(2, Appointment::where('slot_id', $slot->id)->count());
    }

    public function test_row_lock_on_the_slot_blocks_a_concurrent_connection(): void
    {
        // config('database.connections.mysql') is not used here: phpunit.xml's DB_DATABASE=:memory:
        // override (meant for the sqlite connection) bleeds into every connection's env('DB_DATABASE')
        // call, so the resolved mysql config would point at a nonexistent ":memory:" database. These
        // read the real local server directly, with env var overrides for portability.
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

        $companyId = (int) $pdoA->query("SELECT id FROM companies LIMIT 1")->fetchColumn();

        if (! $companyId) {
            $pdoA->exec("INSERT INTO companies (name, code, created_at, updated_at) VALUES ('Concurrency Test Co', 'CTC', NOW(), NOW())");
            $companyId = (int) $pdoA->lastInsertId();
        }

        $branchId = (int) $pdoA->query("SELECT id FROM branches WHERE company_id = {$companyId} LIMIT 1")->fetchColumn();
        if (! $branchId) {
            $pdoA->exec("INSERT INTO branches (company_id, name, code, created_at, updated_at) VALUES ({$companyId}, 'CTC Branch', 'CTCB', NOW(), NOW())");
            $branchId = (int) $pdoA->lastInsertId();
        }

        $doctorId = (int) $pdoA->query('SELECT id FROM users LIMIT 1')->fetchColumn();

        $pdoA->exec("INSERT INTO doctor_schedules (company_id, branch_id, doctor_id, name, day_of_week, start_time, end_time, created_at, updated_at)
            VALUES ({$companyId}, {$branchId}, {$doctorId}, 'Lock Test Session', 'monday', '09:00:00', '13:00:00', NOW(), NOW())");
        $scheduleId = (int) $pdoA->lastInsertId();

        $pdoA->exec("INSERT INTO appointment_slots (company_id, branch_id, doctor_id, schedule_id, slot_datetime, max_capacity, booked_count, status, created_at, updated_at)
            VALUES ({$companyId}, {$branchId}, {$doctorId}, {$scheduleId}, NOW(), 1, 0, 'available', NOW(), NOW())");
        $slotId = (int) $pdoA->lastInsertId();

        try {
            $pdoB->exec('SET SESSION innodb_lock_wait_timeout = 1');

            $pdoA->beginTransaction();
            $pdoA->query("SELECT booked_count FROM appointment_slots WHERE id = {$slotId} FOR UPDATE")->fetch();

            // Connection A now holds the row lock without committing — exactly the state a real
            // in-flight booking transaction would be in. Connection B racing to book the same
            // slot must be blocked, not allowed to read past it and double-book.
            $blocked = false;

            try {
                $pdoB->beginTransaction();
                $pdoB->query("SELECT booked_count FROM appointment_slots WHERE id = {$slotId} FOR UPDATE")->fetch();
                $pdoB->rollBack();
            } catch (\PDOException $e) {
                $blocked = str_contains($e->getMessage(), 'Lock wait timeout exceeded');
                if ($pdoB->inTransaction()) {
                    $pdoB->rollBack();
                }
            }

            $this->assertTrue($blocked, 'A concurrent connection must be blocked from locking the same slot row while a booking transaction is in flight.');

            $pdoA->exec("UPDATE appointment_slots SET booked_count = booked_count + 1 WHERE id = {$slotId}");
            $pdoA->commit();

            // With A committed, B can now acquire the lock and see the up-to-date count.
            $pdoB->beginTransaction();
            $bookedCount = $pdoB->query("SELECT booked_count FROM appointment_slots WHERE id = {$slotId} FOR UPDATE")->fetchColumn();
            $pdoB->rollBack();

            $this->assertSame(1, (int) $bookedCount);
        } finally {
            if ($pdoA->inTransaction()) {
                $pdoA->rollBack();
            }
            $pdoA->exec("DELETE FROM appointment_slots WHERE id = {$slotId}");
            $pdoA->exec("DELETE FROM doctor_schedules WHERE id = {$scheduleId}");
        }
    }
}
