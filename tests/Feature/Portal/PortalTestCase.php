<?php

namespace Tests\Feature\Portal;

use App\Models\AppointmentSlot;
use App\Models\Billing\BillingInvoice;
use App\Models\Branch;
use App\Models\Department;
use App\Models\DoctorSchedule;
use App\Models\Encounter;
use App\Models\Laboratory\LabOrder;
use App\Models\Laboratory\LabReport;
use App\Models\Laboratory\LabResult;
use App\Models\Laboratory\LabSpecimenType;
use App\Models\Laboratory\LabTest;
use App\Models\Laboratory\LabTestCategory;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\PatientPortalAccount;
use App\Models\Portal\PortalOutbox;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\Provider;
use App\Models\User;
use App\Services\Portal\PortalAuthService;
use App\Services\TenantContextResolver;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

abstract class PortalTestCase extends TestCase
{
    use RefreshDatabase;

    protected Organization $organization;

    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-10 10:00:00'); // a Thursday morning in Dhaka is 04:00 UTC; 10:00 UTC is 16:00 local
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->organization = Organization::factory()->create(['code' => 'DEMO', 'is_active' => true]);
        $this->branch = Branch::factory()->create(['organization_id' => $this->organization->id]);
        app(TenantContextResolver::class)->setOrganizationId($this->organization->id);
        app(TenantContextResolver::class)->setBranchId($this->branch->id);
        config(['portal.payments.driver' => 'sandbox']);
    }

    /** One app instance serves the whole test, so a resolved login would carry over to the next request; a real request starts clean. */
    public function json($method, $uri, array $data = [], array $headers = [], $options = 0)
    {
        app('auth')->forgetGuards();

        return parent::json($method, $uri, $data, $headers, $options);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** A patient who can register: phone, date of birth and patient number are all on file. @param  array<string, mixed>  $o */
    protected function patient(array $o = []): Patient
    {
        return Patient::factory()->create(array_merge([
            'organization_id' => $this->organization->id, 'enterprise_patient_no' => 'EPT-'.fake()->unique()->numerify('######'), 'phone' => '01712'.fake()->unique()->numerify('######'),
            'date_of_birth' => '1990-04-12', 'first_name' => 'Rina', 'last_name' => 'Akter', 'national_identifier' => null,
        ], $o));
    }

    /** A signed-up login for a patient. @param  array<string, mixed>  $o */
    protected function account(?Patient $patient = null, array $o = []): PatientPortalAccount
    {
        $patient ??= $this->patient();

        return PatientPortalAccount::factory()->create(array_merge(['patient_id' => $patient->id, 'organization_id' => $patient->organization_id], $o));
    }

    /** The newest six-digit code in the outbox for a phone number. */
    protected function lastCode(string $phone): string
    {
        $row = PortalOutbox::query()->where('template', 'otp')->where('to', $phone)->latest('id')->firstOrFail();
        preg_match('/\d{6}/', $row->body, $m);

        return $m[0];
    }

    /** @param  list<string>  $permissions */
    protected function actor(array $permissions): User
    {
        $user = User::factory()->create();
        $user->organizations()->attach($this->organization->id, ['access_level' => 'admin']);
        $user->branches()->attach($this->branch->id, ['access_level' => 'manager', 'organization_id' => $this->organization->id]);
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $user->givePermissionTo($permissions);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user->refresh();
    }

    protected function superAdmin(): User
    {
        $user = User::factory()->create();
        $user->organizations()->attach($this->organization->id, ['access_level' => 'admin']);
        $user->branches()->attach($this->branch->id, ['access_level' => 'manager', 'organization_id' => $this->organization->id]);
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $user->assignRole('super_admin');

        return $user;
    }

    // ---- fixtures -------------------------------------------------------------------------------------------------------------------------------------------

    protected function provider(string $name = 'Dr. Karim'): Provider
    {
        return Provider::factory()->create(['organization_id' => $this->organization->id, 'branch_id' => $this->branch->id, 'name' => $name, 'status' => 'active']);
    }

    /** An open slot tomorrow at the given hour (local time does not matter: it is stored as given). */
    protected function slot(Provider $provider, string $at = '+1 day 10:00'): AppointmentSlot
    {
        $doctor = User::factory()->create();
        $schedule = DoctorSchedule::factory()->create(['organization_id' => $this->organization->id, 'branch_id' => $this->branch->id, 'doctor_id' => $doctor->id, 'provider_id' => $provider->id]);

        return AppointmentSlot::factory()->create(['organization_id' => $this->organization->id, 'branch_id' => $this->branch->id, 'doctor_id' => $doctor->id, 'schedule_id' => $schedule->id,
            'slot_datetime' => now()->modify($at), 'duration_minutes' => 30, 'max_capacity' => 1, 'booked_count' => 0, 'status' => 'available']);
    }

    protected function invoice(Patient $patient, string $total = '1000.00', string $paid = '0.00', string $status = 'finalized'): BillingInvoice
    {
        return BillingInvoice::query()->create(['organization_id' => $this->organization->id, 'branch_id' => $this->branch->id, 'patient_id' => $patient->id, 'invoice_number' => 'INV-'.uniqid(), 'invoice_type' => 'opd', 'status' => $status,
            'currency' => 'BDT', 'subtotal' => $total, 'grand_total' => $total, 'paid_amount' => $paid, 'due_amount' => bcsub($total, $paid, 2), 'invoice_date' => now()->toDateString(), 'created_by' => User::factory()->create()->id]);
    }

    protected function prescription(Patient $patient, ?string $issuedAt = 'now'): Prescription
    {
        $doctor = User::factory()->create();
        $rx = Prescription::factory()->create(['organization_id' => $this->organization->id, 'branch_id' => $this->branch->id, 'patient_id' => $patient->id, 'doctor_id' => $doctor->id, 'appointment_id' => null, 'issued_at' => $issuedAt ? now()->modify($issuedAt) : null, 'status' => 'issued']);
        PrescriptionItem::query()->create(['organization_id' => $this->organization->id, 'prescription_id' => $rx->id, 'medicine_name' => 'Paracetamol', 'strength' => '500 mg', 'frequency' => 'TDS', 'duration' => '5 days', 'is_active' => true]);

        return $rx;
    }

    /** A final lab report with one result. */
    protected function labReport(Patient $patient, string $testName = 'Hemoglobin', bool $critical = false, ?string $category = null): LabReport
    {
        $user = User::factory()->create();
        $dept = Department::query()->where('organization_id', $this->organization->id)->first() ?? Department::create(['organization_id' => $this->organization->id, 'branch_id' => $this->branch->id, 'name' => 'Lab', 'code' => 'L-'.uniqid(), 'is_active' => true]);
        $enc = Encounter::create(['organization_id' => $this->organization->id, 'branch_id' => $this->branch->id, 'patient_id' => $patient->id, 'encounter_no' => 'ENC-'.uniqid(), 'encounter_type' => 'opd', 'provider_id' => $user->id, 'department_id' => $dept->id,
            'encounter_date' => now()->toDateString(), 'status' => 'completed', 'source' => 'walk_in', 'created_by' => $user->id]);
        $cat = $category ? LabTestCategory::query()->firstOrCreate(['organization_id' => $this->organization->id, 'code' => strtoupper($category)], ['name' => $category, 'is_active' => true]) : null;
        $spec = LabSpecimenType::query()->firstOrCreate(['organization_id' => $this->organization->id, 'code' => 'BLOOD'], ['name' => 'Blood', 'is_active' => true]);
        $test = LabTest::create(['organization_id' => $this->organization->id, 'code' => 'T'.uniqid(), 'name' => $testName, 'test_type' => 'quantitative', 'unit' => 'g/dL', 'specimen_type_id' => $spec->id, 'category_id' => $cat?->id, 'is_active' => true]);
        $order = LabOrder::create(['organization_id' => $this->organization->id, 'branch_id' => $this->branch->id, 'department_id' => $dept->id, 'patient_id' => $patient->id, 'encounter_id' => $enc->id, 'order_number' => 'LAB-'.uniqid(),
            'priority' => 'routine', 'status' => 'reported', 'ordered_by' => $user->id, 'ordered_at' => now()]);
        $item = $order->items()->create(['test_id' => $test->id, 'requested_test_name' => $testName, 'status' => 'reported', 'result_status' => 'final', 'requested_at' => now()]);
        LabResult::create(['organization_id' => $this->organization->id, 'branch_id' => $this->branch->id, 'lab_order_item_id' => $item->id, 'test_id' => $test->id, 'result_type' => 'numeric', 'numeric_value' => 13.5, 'unit' => 'g/dL',
            'reference_range_low' => 12, 'reference_range_high' => 16, 'critical_flag' => $critical, 'result_status' => 'final', 'is_current' => true, 'reported_at' => now()]);

        return LabReport::create(['organization_id' => $this->organization->id, 'branch_id' => $this->branch->id, 'lab_order_id' => $order->id, 'report_number' => 'LR-'.uniqid(), 'status' => 'final', 'generated_by' => $user->id, 'generated_at' => now()]);
    }

    /** A signed-in mobile session: returns the Bearer headers. @return array<string, string> */
    protected function bearer(PatientPortalAccount $account): array
    {
        [, $plain] = app(PortalAuthService::class)->registerDevice($account, 'test-device-'.$account->id, 'Test phone', 'android', true);

        return ['Authorization' => 'Bearer '.$plain, 'Accept' => 'application/json'];
    }
}
