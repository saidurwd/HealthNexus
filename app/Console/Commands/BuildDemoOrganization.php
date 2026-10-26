<?php

namespace App\Console\Commands;

use App\Models\Billing\BillingCategory;
use App\Models\Branch;
use App\Models\Ipd\IpdRoom;
use App\Models\Ipd\IpdWard;
use App\Models\Organization;
use App\Models\Pharmacy\PharmacyStore;
use App\Models\User;
use App\Services\Import\ImportContext;
use App\Services\Import\ImportService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

/**
 * Builds a complete fake "training" hospital (guideline H-11): organization, branch, staff login, wards and beds, price list,
 * drug catalogue with stock, lab tests and patients with Bangla and English names. Every catalogue goes in through the real
 * Import Service, so building it also exercises the importers. Every run makes a NEW training organization; nothing is deleted.
 */
class BuildDemoOrganization extends Command
{
    protected $signature = 'demo:build {--patients=300 : Number of fake patients} {--password= : Password for the training admin (generated if omitted)}
        {--retire-old : Deactivate earlier training organizations} {--history= : Also give every module this many months of history (use 6) and create test users by module} {--allow-production : Run even when APP_ENV=production}';

    protected $description = 'Create a training organization full of clearly fake data (names, numbers and e-mails are invented)';

    private const GIVEN_EN = ['Rahim', 'Karim', 'Jamal', 'Nasrin', 'Fatema', 'Mohammad', 'Abdul', 'Sultana', 'Hasan', 'Ayesha', 'Imran', 'Shirin', 'Tanvir', 'Rokeya', 'Habib', 'Salma', 'Faruk', 'Nusrat', 'Kamal', 'Rina'];

    private const GIVEN_BN = ['রহিম', 'করিম', 'জামাল', 'নাসরিন', 'ফাতেমা', 'মোহাম্মদ', 'আব্দুল', 'সুলতানা', 'হাসান', 'আয়েশা', 'ইমরান', 'শিরিন', 'তানভীর', 'রোকেয়া', 'হাবিব', 'সালমা', 'ফারুক', 'নুসরাত', 'কামাল', 'রিনা'];

    private const FAMILY_EN = ['Uddin', 'Rahman', 'Hossain', 'Akter', 'Khan', 'Islam', 'Begum', 'Ahmed', 'Chowdhury', 'Sarker', 'Mia', 'Khatun', 'Das', 'Roy', 'Talukder'];

    private const FAMILY_BN = ['উদ্দিন', 'রহমান', 'হোসেন', 'আক্তার', 'খান', 'ইসলাম', 'বেগম', 'আহমেদ', 'চৌধুরী', 'সরকার', 'মিয়া', 'খাতুন', 'দাস', 'রায়', 'তালুকদার'];

    public function handle(ImportService $imports): int
    {
        if (app()->isProduction() && ! $this->option('allow-production')) {
            $this->error('Refusing to create fake data in production. (Use --allow-production only on a separate training server.)');

            return self::FAILURE;
        }

        $this->callSilent('db:seed', ['--class' => PermissionSeeder::class, '--force' => true]);

        $this->option('retire-old') && Organization::query()->where('code', 'like', 'TRAIN%')->update(['is_active' => false]);

        $stamp = now()->format('ymdHis');
        $organization = Organization::create([
            'name' => 'HealthNexus Training Hospital', 'code' => 'TRAIN'.$stamp, 'slug' => 'training-'.$stamp,
            'settings' => ['training' => true], 'is_active' => true,
        ]);
        $branch = Branch::create(['organization_id' => $organization->id, 'name' => 'Main Campus', 'code' => 'MAIN', 'slug' => 'main', 'is_active' => true]);

        $password = $this->option('password') ?: Str::password(14, symbols: false);
        $admin = User::create(['name' => 'Training Administrator', 'email' => "training.admin.$stamp@example.invalid", 'password' => Hash::make($password), 'is_active' => true]);
        $admin->organizations()->attach($organization->id, ['access_level' => 'admin']);
        $admin->branches()->attach($branch->id, ['access_level' => 'manager', 'organization_id' => $organization->id]);
        $admin->assignRole('hospital_admin');
        $admin->givePermissionTo(Permission::where('name', 'like', 'import.%')->get());

        $context = new ImportContext($organization->id, $branch->id, $admin);
        $this->scaffold($organization, $branch);

        $steps = [
            'services' => $this->services(),
            'medications' => $this->medications(),
            'lab_tests' => $this->labTests(),
            'opening_stock' => $this->openingStock(),
            'beds' => $this->beds(),
            'users' => $this->staff(),
            'patients' => $this->patients((int) $this->option('patients')),
        ];

        foreach ($steps as $key => $rows) {
            $path = tempnam(sys_get_temp_dir(), "demo-$key");
            file_put_contents($path, $this->toCsv($rows));
            $batch = $imports->commit($imports->validate($imports->upload($admin, $context, $key, $path, "demo-$key.csv")), $admin);
            unlink($path);
            $this->line(sprintf('  %-14s %4d imported, %d skipped', $key, $batch->imported_rows, $batch->total_rows - $batch->imported_rows));
        }

        if ($months = (int) $this->option('history')) {
            // The catalogue seeders are idempotent; they give the new organization the wards, theatres, ICU beds, imaging and drug catalogues the history needs.
            foreach ([\Database\Seeders\BillingSeeder::class, \Database\Seeders\LabSeeder::class, \Database\Seeders\RadiologySeeder::class, \Database\Seeders\PharmacySeeder::class, \Database\Seeders\IpdSeeder::class, \Database\Seeders\NursingSeeder::class, \Database\Seeders\OtSeeder::class, \Database\Seeders\IcuSeeder::class, \Database\Seeders\WorkflowSeeder::class] as $seeder) {
                $this->callSilent('db:seed', ['--class' => $seeder, '--force' => true]);
            }
            $this->call('demo:history', ['organization' => $organization->code, '--months' => $months, '--password' => $password]);
        }

        $this->newLine();
        $this->info("Training organization {$organization->code} is ready.");
        $this->line("  Sign in as:  {$admin->email}");
        $this->line("  Password:    $password   (shown once; this is a training login)");
        $this->warn('All patients, phone numbers and e-mails are made up. Do not send real SMS or e-mail from a training organization.');

        return self::SUCCESS;
    }

    /** Things the importers expect to exist already: billing categories, a pharmacy store, a ward with rooms. */
    private function scaffold(Organization $organization, Branch $branch): void
    {
        foreach ([['CONSULT', 'Consultation'], ['LAB', 'Laboratory'], ['RAD', 'Radiology'], ['BED', 'Bed charges']] as [$code, $name]) {
            BillingCategory::create(['organization_id' => $organization->id, 'branch_id' => null, 'name' => $name, 'code' => $code, 'is_active' => true]);
        }
        PharmacyStore::create(['organization_id' => $organization->id, 'branch_id' => null, 'code' => 'PH-MAIN', 'name' => 'Main Pharmacy', 'store_type' => 'main', 'is_active' => true]);

        foreach ([['MED', 'Medicine Ward'], ['SUR', 'Surgical Ward']] as [$code, $name]) {
            $ward = IpdWard::create(['organization_id' => $organization->id, 'branch_id' => null, 'code' => $code, 'name' => $name, 'ward_type' => 'general', 'gender_policy' => 'any', 'capacity' => 10, 'isolation_capable' => false, 'is_active' => true]);
            foreach (range(1, 5) as $n) {
                IpdRoom::create(['organization_id' => $organization->id, 'branch_id' => null, 'ward_id' => $ward->id, 'room_number' => (string) ($n + 100), 'room_type' => 'general', 'capacity' => 2, 'gender_policy' => 'any', 'is_active' => true]);
            }
        }
    }

    private function services(): array
    {
        $rows = [['item_code', 'name', 'category_code', 'base_price']];
        foreach ([['OPD-CONS', 'OPD Consultation', 'CONSULT', 500], ['OPD-SPEC', 'Specialist Consultation', 'CONSULT', 1000], ['OPD-FUP', 'Follow-up Visit', 'CONSULT', 300], ['BED-GEN', 'General Bed (per day)', 'BED', 1200], ['BED-CAB', 'Cabin (per day)', 'BED', 3500],
            ['ED-VISIT', 'Emergency Visit', 'CONSULT', 800], ['ED-RESUS', 'Resuscitation', 'CONSULT', 3000], ['ED-PROC', 'Emergency Procedure', 'CONSULT', 600], ['ED-OBS-HR', 'Observation Unit (per hour)', 'BED', 250],
            ['RAD-XR-CHEST', 'X-Ray Chest', 'RAD', 600], ['RAD-USG-ABD', 'Ultrasound Abdomen', 'RAD', 1200], ['RAD-CT-HEAD', 'CT Scan Head', 'RAD', 5500],
            ['LAB-CBC', 'Complete Blood Count', 'LAB', 450], ['LAB-RBS', 'Random Blood Sugar', 'LAB', 150], ['LAB-CREAT', 'Serum Creatinine', 'LAB', 400], ['LAB-LFT', 'Liver Function Test', 'LAB', 1100], ['LAB-URINE', 'Urine R/E', 'LAB', 200]] as $r) {
            $rows[] = $r;
        }

        return $rows;
    }

    private function medications(): array
    {
        $rows = [['code', 'name', 'strength', 'strength_unit', 'pack_size', 'dispensing_unit', 'manufacturer', 'is_prescription_required']];
        foreach ([['PARA-500', 'Paracetamol 500 mg Tablet', 500, 'mg', 10, 'tablet', 'Demo Pharma Ltd', 'no'], ['AMOX-500', 'Amoxicillin 500 mg Capsule', 500, 'mg', 10, 'capsule', 'Demo Pharma Ltd', 'yes'], ['OMEP-20', 'Omeprazole 20 mg Capsule', 20, 'mg', 10, 'capsule', 'Demo Pharma Ltd', 'yes'],
            ['METF-500', 'Metformin 500 mg Tablet', 500, 'mg', 10, 'tablet', 'Sample Labs', 'yes'], ['AMLO-5', 'Amlodipine 5 mg Tablet', 5, 'mg', 10, 'tablet', 'Sample Labs', 'yes'], ['ORS', 'Oral Rehydration Salt', 20, 'g', 1, 'sachet', 'Sample Labs', 'no'],
            ['SALB-INH', 'Salbutamol Inhaler', 100, 'mcg', 1, 'inhaler', 'Demo Pharma Ltd', 'yes'], ['CEFU-500', 'Cefuroxime 500 mg Tablet', 500, 'mg', 10, 'tablet', 'Sample Labs', 'yes']] as $r) {
            $rows[] = $r;
        }

        return $rows;
    }

    private function labTests(): array
    {
        $rows = [['code', 'name', 'short_name', 'test_type', 'unit', 'fasting_required', 'turnaround_time_minutes']];
        foreach ([['CBC', 'Complete Blood Count', 'CBC', 'quantitative', '', 'no', 120], ['RBS', 'Random Blood Sugar', 'RBS', 'quantitative', 'mmol/L', 'no', 30], ['FBS', 'Fasting Blood Sugar', 'FBS', 'quantitative', 'mmol/L', 'yes', 60],
            ['CREAT', 'Serum Creatinine', 'Creat', 'quantitative', 'umol/L', 'no', 90], ['URINE-RE', 'Urine Routine Examination', 'Urine R/E', 'qualitative', '', 'no', 60], ['HBSAG', 'HBsAg', 'HBsAg', 'qualitative', '', 'no', 60]] as $r) {
            $rows[] = $r;
        }

        return $rows;
    }

    private function openingStock(): array
    {
        $rows = [['store_code', 'medication_code', 'batch_number', 'expiry_date', 'quantity', 'unit_cost', 'selling_price']];
        foreach (['PARA-500', 'AMOX-500', 'OMEP-20', 'METF-500', 'AMLO-5', 'ORS', 'SALB-INH', 'CEFU-500'] as $i => $code) {
            $rows[] = ['PH-MAIN', $code, 'TRN-'.sprintf('%03d', $i + 1), now()->addMonths(10 + $i)->toDateString(), 400 + $i * 50, 1 + $i * 0.5, 1.5 + $i * 0.5];
        }

        return $rows;
    }

    private function beds(): array
    {
        $rows = [['ward_code', 'room_number', 'bed_code', 'bed_name', 'gender_type', 'oxygen_available']];
        foreach (['MED', 'SUR'] as $ward) {
            foreach (range(1, 5) as $room) {
                foreach (['A', 'B'] as $bed) {
                    $rows[] = [$ward, (string) (100 + $room), "$ward-".(100 + $room)."-$bed", "Bed $bed", 'any', $bed === 'A' ? 'yes' : 'no'];
                }
            }
        }

        return $rows;
    }

    private function staff(): array
    {
        $rows = [['name', 'email', 'role']];
        foreach ([['Dr. Rahim Uddin', 'doctor'], ['Dr. Nasrin Akter', 'doctor'], ['Sultana Begum', 'nurse'], ['Karim Mia', 'receptionist'], ['Fatema Khatun', 'cashier'], ['Hasan Ahmed', 'pharmacist'], ['Jamal Hossain', 'lab_technician']] as $i => [$name, $role]) {
            $rows[] = [$name, 'training.staff'.($i + 1).'.'.now()->format('ymdHis').'@example.invalid', $role];
        }

        return $rows;
    }

    private function patients(int $count): array
    {
        mt_srand(2026); // same names every build
        $rows = [['first_name', 'last_name', 'date_of_birth', 'sex', 'phone', 'national_identifier', 'blood_group', 'address', 'city']];
        $groups = ['A+', 'B+', 'O+', 'AB+', 'A-', 'B-', 'O-'];

        for ($i = 0; $i < $count; $i++) {
            $g = $i % count(self::GIVEN_EN);
            $f = intdiv($i, count(self::GIVEN_EN)) % count(self::FAMILY_EN);
            $bangla = $i % 3 === 0;
            $female = in_array($g, [3, 4, 7, 9, 11, 13, 15, 17, 19], true);
            $rows[] = [
                $bangla ? self::GIVEN_BN[$g] : self::GIVEN_EN[$g], $bangla ? self::FAMILY_BN[$f] : self::FAMILY_EN[$f],
                now()->subYears(mt_rand(1, 85))->subDays(mt_rand(0, 364))->toDateString(), $female ? 'F' : 'M',
                sprintf('0171%07d', 1000000 + $i), sprintf('199%010d', 5000000000 + $i), $groups[$i % count($groups)],
                'House '.(1 + $i % 90).', Road '.(1 + $i % 30).', Demo Area', ['Dhaka', 'Chattogram', 'Sylhet', 'Khulna', 'Rajshahi'][$i % 5],
            ];
        }

        return $rows;
    }

    private function toCsv(array $rows): string
    {
        $out = fopen('php://memory', 'w+');
        foreach ($rows as $row) {
            fputcsv($out, $row);
        }
        rewind($out);

        return "\xEF\xBB\xBF".stream_get_contents($out);
    }
}
