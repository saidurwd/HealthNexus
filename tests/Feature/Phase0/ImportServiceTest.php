<?php

namespace Tests\Feature\Phase0;

use App\Jobs\Import\CommitImportBatch;
use App\Jobs\Import\ValidateImportBatch;
use App\Models\Branch;
use App\Models\Billing\BillingCategory;
use App\Models\Billing\BillingItem;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\Pharmacy\PharmacyBatch;
use App\Models\Pharmacy\PharmacyMedication;
use App\Models\Pharmacy\PharmacyStock;
use App\Models\Pharmacy\PharmacyStore;
use App\Models\User;
use App\Services\Import\ImportContext;
use App\Services\Import\ImportService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/** H-11: staged import with duplicate detection, error file and whole-batch rollback. */
class ImportServiceTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    private Branch $branch;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->organization = Organization::factory()->create();
        $this->branch = Branch::factory()->create(['organization_id' => $this->organization->id]);
        $this->user = User::factory()->create();
        $this->user->organizations()->attach($this->organization->id, ['access_level' => 'admin']);
        $this->user->branches()->attach($this->branch->id, ['access_level' => 'manager', 'organization_id' => $this->organization->id]);
        foreach (['patients', 'services', 'medications', 'lab_tests', 'beds', 'users', 'opening_stock'] as $type) {
            Permission::firstOrCreate(['name' => "import.$type", 'guard_name' => 'web']);
            $this->user->givePermissionTo("import.$type");
        }
        $this->actingAs($this->user);
    }

    private function context(): ImportContext
    {
        return new ImportContext($this->organization->id, $this->branch->id, $this->user);
    }

    private function csv(array $lines): string
    {
        $path = tempnam(sys_get_temp_dir(), 'imp');
        file_put_contents($path, "\xEF\xBB\xBF".implode("\n", $lines));

        return $path;
    }

    private function importFile(string $key, array $lines, bool $commit = true)
    {
        $service = app(ImportService::class);
        $batch = $service->upload($this->user, $this->context(), $key, $this->csv($lines), "$key.csv");
        $batch = $service->validate($batch);

        return $commit ? $service->commit($batch, $this->user) : $batch;
    }

    public function test_patients_import_validates_dedupes_and_reports_every_problem(): void
    {
        Patient::factory()->create(['organization_id' => $this->organization->id, 'national_identifier' => '1990123456789', 'enterprise_patient_no' => 'EXI-00000001']);

        $batch = $this->importFile('patients', [
            'first_name,last_name,date_of_birth,sex,phone,national_identifier,blood_group',
            'Rahim,Uddin,1985-03-14,M,01712345678,,O+',                    // valid
            'Karim,Hossain,14/03/1990,Male,+8801811111111,,A-',            // valid, other formats
            'করিম,হোসেন,১৯৯২-০১-০১,M,০১৭১৯৯৯৯৯৯৯,,B+',                      // Bangla digits in date: still valid? (date must be ASCII after conversion)
            'Nasrin,Akter,1980-01-01,F,01912345678,1990123456789,',         // duplicate of existing NID
            'Rahim,Uddin,1985-03-14,M,01712345678,,O+',                    // duplicate of row 2 in the file
            ',Missing,1990-01-01,M,01712345679,,',                          // first_name missing
            'Bad,Phone,1990-01-01,M,12345,,',                               // bad phone
            'Bad,Date,31/02/2020,M,01712345670,,',                          // impossible date
            'Bad,Sex,1990-01-01,X,01712345671,,',                           // bad sex
        ], commit: false);

        $this->assertSame(9, $batch->total_rows);
        $this->assertSame(3, $batch->valid_rows);
        $this->assertSame(2, $batch->duplicate_rows);
        $this->assertSame(4, $batch->error_rows);
        $this->assertStringContainsString('same national ID', $batch->rows()->where('row_number', 5)->value('duplicate_of'));
        $this->assertStringContainsString('row 2 of this file', $batch->rows()->where('row_number', 6)->value('duplicate_of'));
        $this->assertSame(1, Patient::where('organization_id', $this->organization->id)->count(), 'nothing is written before commit');
    }

    public function test_commit_creates_patients_with_numbers_skips_duplicates_and_error_file_lists_the_rest(): void
    {
        $batch = $this->importFile('patients', [
            'first_name,last_name,phone,national_identifier',
            'Rahim,Uddin,01712345678,1990000000001',
            'Rahim,Again,01712345679,1990000000001',   // same NID as above
            ',NoFirst,01712345670,',
        ]);

        $this->assertSame('committed', $batch->status);
        $this->assertSame(1, $batch->imported_rows);
        $patient = Patient::where('national_identifier', '1990000000001')->firstOrFail();
        $this->assertNotNull($patient->enterprise_patient_no);
        $this->assertDatabaseHas('patient_search_index', ['patient_id' => $patient->id, 'kind' => 'phone', 'term' => '1712345678']);

        $content = $this->captureDownload($batch);
        $this->assertStringContainsString('first_name is required', $content);
        $this->assertStringContainsString('Duplicate of row 2 of this file', $content);
    }

    private function captureDownload($batch): string
    {
        ob_start();
        app(ImportService::class)->errorFile($batch)->sendContent();

        return ob_get_clean();
    }

    public function test_rollback_removes_unused_patients_and_keeps_those_with_activity(): void
    {
        $batch = $this->importFile('patients', ['first_name,last_name,phone', 'Aa,Bb,01711000001', 'Cc,Dd,01711000002']);
        $used = Patient::where('phone', '01711000002')->firstOrFail();
        DB::table('appointments')->count(); // table exists
        \App\Models\Appointment::factory()->create(['organization_id' => $this->organization->id, 'patient_id' => $used->id]);

        $rolled = app(ImportService::class)->rollback($batch, $this->user);

        $this->assertSame('rolled_back', $rolled->status);
        $this->assertNull(Patient::where('phone', '01711000001')->first());
        $this->assertNotNull(Patient::where('phone', '01711000002')->first());
        $this->assertSame(1, $rolled->rows()->where('status', 'kept')->count());
        $this->assertSame(0, DB::table('patient_search_index')->where('term', '1711000001')->count());
    }

    public function test_missing_columns_wrong_permission_and_empty_files_are_refused(): void
    {
        $service = app(ImportService::class);

        foreach ([
            fn () => $service->upload($this->user, $this->context(), 'patients', $this->csv(['first_name,phone', 'A,017']), 'x.csv'),
            fn () => $service->upload($this->user, $this->context(), 'patients', $this->csv(['first_name,last_name']), 'x.csv'),
        ] as $call) {
            try {
                $call();
                $this->fail('expected a validation error');
            } catch (ValidationException) {
                $this->assertTrue(true);
            }
        }

        $nobody = User::factory()->create();
        $this->expectException(AuthorizationException::class);
        $service->upload($nobody, $this->context(), 'patients', $this->csv(['first_name,last_name', 'A,B']), 'x.csv');
    }

    public function test_commit_requires_a_validated_batch_and_cannot_run_twice(): void
    {
        $service = app(ImportService::class);
        $batch = $service->upload($this->user, $this->context(), 'patients', $this->csv(['first_name,last_name', 'A,B']), 'x.csv');

        try {
            $service->commit($batch, $this->user);
            $this->fail('unvalidated batch must not commit');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }

        $service->commit($service->validate($batch), $this->user);
        $this->expectException(ValidationException::class);
        $service->commit($batch->refresh(), $this->user);
    }

    public function test_services_and_medications_and_their_rollback(): void
    {
        BillingCategory::create(['organization_id' => $this->organization->id, 'branch_id' => null, 'name' => 'Consultation', 'code' => 'CONSULT', 'is_active' => true]);

        $services = $this->importFile('services', [
            'item_code,name,category_code,base_price',
            'OPD-1,OPD Consultation,CONSULT,500',
            'OPD-2,Specialist,NOPE,800',
            'OPD-3,Negative,CONSULT,-5',
        ]);
        $meds = $this->importFile('medications', ['code,name,strength,strength_unit,pack_size', 'NAPA-500,Napa 500,500,mg,10', 'NAPA-500,Napa again,500,mg,10']);

        $this->assertSame(1, $services->imported_rows);
        $this->assertSame(2, $services->error_rows);
        $this->assertSame('500.00', (string) BillingItem::where('item_code', 'OPD-1')->value('base_price'));
        $this->assertSame(1, $meds->imported_rows);
        $this->assertSame(1, $meds->duplicate_rows);

        // A charge uses OPD-1, so rolling back must deactivate rather than delete it.
        $item = BillingItem::where('item_code', 'OPD-1')->first();
        $patient = Patient::factory()->create(['organization_id' => $this->organization->id]);
        DB::table('billing_charges')->insert([
            'organization_id' => $this->organization->id, 'branch_id' => $this->branch->id, 'patient_id' => $patient->id, 'billing_item_id' => $item->id,
            'idempotency_key' => 'k', 'status' => 'pending', 'quantity' => 1, 'currency' => 'BDT', 'unit_price' => 500, 'gross_amount' => 500,
            'discount_type' => 'none', 'discount_amount' => 0, 'tax_amount' => 0, 'net_amount' => 500, 'created_at' => now(), 'updated_at' => now(),
        ]);

        app(ImportService::class)->rollback($services, $this->user);
        app(ImportService::class)->rollback($meds, $this->user);

        $this->assertFalse((bool) BillingItem::find($item->id)->is_active);
        $this->assertNull(PharmacyMedication::where('code', 'NAPA-500')->first());
    }

    public function test_opening_stock_goes_through_the_stock_service_and_rolls_back_through_the_ledger(): void
    {
        PharmacyStore::create(['organization_id' => $this->organization->id, 'branch_id' => null, 'code' => 'PH-MAIN', 'name' => 'Main', 'store_type' => 'main', 'is_active' => true]);
        $this->importFile('medications', ['code,name', 'NAPA-500,Napa 500']);
        $future = now()->addYear()->toDateString();

        $batch = $this->importFile('opening_stock', [
            'store_code,medication_code,batch_number,expiry_date,quantity,unit_cost',
            "PH-MAIN,NAPA-500,B1,$future,500,1.20",
            "PH-MAIN,NAPA-500,B2,2020-01-01,5,1",
            "PH-MAIN,NOPE,B3,$future,5,1",
        ]);

        $this->assertSame(1, $batch->imported_rows);
        $stock = PharmacyStock::firstOrFail();
        $this->assertSame(500, $stock->quantity_available);
        $this->assertSame(1, DB::table('stock_ledger')->where('txn_type', 'opening_balance')->count());

        app(ImportService::class)->rollback($batch, $this->user);

        $this->assertSame(0, $stock->fresh()->quantity_available);
        $this->assertSame(0.0, (float) DB::table('v_stock_on_hand_all_stores')->sum('on_hand'));
        $this->assertNotNull(PharmacyBatch::where('batch_number', 'B1')->first(), 'the batch record stays for history');
    }

    public function test_users_import_creates_inactive_accounts_with_no_usable_password(): void
    {
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'nurse', 'guard_name' => 'web']);

        $batch = $this->importFile('users', ['name,email,role', 'Nasrin Akter,nasrin@example.org,nurse', 'Bad Role,bad@example.org,wizard', 'Nasrin Again,NASRIN@example.org,nurse']);
        $user = User::where('email', 'nasrin@example.org')->firstOrFail();

        $this->assertSame(1, $batch->imported_rows);
        $this->assertFalse((bool) $user->is_active);
        $this->assertTrue($user->hasRole('nurse'));
        $this->assertTrue($user->organizations()->whereKey($this->organization->id)->exists());
        $this->assertFalse(\Illuminate\Support\Facades\Hash::check('password', $user->password));
    }

    /** Set IMPORT_LOAD_ROWS=5000 to run the guideline's 5,000-row acceptance size (about two minutes). */
    public function test_large_patient_file_imports_with_duplicates_and_an_error_report(): void
    {
        $rows = (int) (getenv('IMPORT_LOAD_ROWS') ?: 600);
        $lines = ['first_name,last_name,date_of_birth,phone,national_identifier'];
        for ($i = 1; $i <= $rows; $i++) {
            $nid = $i % 100 === 0 ? '' : sprintf('19%011d', $i);               // 50 rows without NID
            $phone = $i % 50 === 0 ? '12345' : sprintf('0171%07d', $i);       // 20 bad phones
            $lines[] = sprintf('Name%d,Family%d,1980-01-%02d,%s,%s', $i, $i % 50, 1 + $i % 28, $phone, $nid);
        }
        for ($i = 1; $i <= 40; $i++) {
            $lines[] = sprintf('Name%d,Family%d,1980-01-%02d,%s,%s', $i, $i % 50, 1 + $i % 28, sprintf('0171%07d', $i), sprintf('19%011d', $i)); // 40 repeated rows
        }

        $started = microtime(true);
        $batch = $this->importFile('patients', $lines);

        $badPhones = intdiv($rows, 50);
        $this->assertSame($rows + 40, $batch->total_rows);
        $this->assertSame($badPhones, $batch->error_rows);
        $this->assertGreaterThanOrEqual(40, $batch->duplicate_rows);
        $this->assertSame(Patient::where('organization_id', $this->organization->id)->count(), $batch->imported_rows);
        $this->assertGreaterThan($rows - $badPhones - 5, $batch->imported_rows);
        $errors = $this->captureDownload($batch);
        $this->assertStringContainsString('phone must be a Bangladeshi mobile number', $errors);
        $this->assertStringContainsString('Duplicate of', $errors);
        fwrite(STDERR, sprintf("\n%d-row patient import: %.1fs (%d imported, %d duplicates, %d errors)\n", $rows + 40, microtime(true) - $started, $batch->imported_rows, $batch->duplicate_rows, $batch->error_rows));
    }

    public function test_jobs_validate_then_commit(): void
    {
        $batch = app(ImportService::class)->upload($this->user, $this->context(), 'patients', $this->csv(['first_name,last_name', 'Job,Test']), 'j.csv');

        ValidateImportBatch::dispatchSync($batch->id);
        CommitImportBatch::dispatchSync($batch->id, $this->user->id);

        $this->assertSame('committed', $batch->refresh()->status);
        $this->assertSame(1, Patient::where('first_name', 'Job')->count());
    }
}
