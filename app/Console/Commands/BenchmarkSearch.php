<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Models\Patient;
use App\Models\User;
use App\Services\Search\GlobalSearchService;
use App\Services\Search\Normalizer;
use App\Services\TenantContextResolver;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Measures the H-09 target (patient search p95 <= 500 ms on 100k patients). Seeds clearly fake patients into a throw-away
 * organization, times the search, then removes everything. Run it against a scratch database, never production.
 */
class BenchmarkSearch extends Command
{
    protected $signature = 'search:benchmark {count=100000} {--keep : Do not delete the seeded patients}';

    protected $description = 'Seed fake patients and time the global search (dev/test databases only)';

    public function handle(GlobalSearchService $search, TenantContextResolver $tenant): int
    {
        if (app()->isProduction()) {
            $this->error('Refusing to run in production.');

            return self::FAILURE;
        }

        $count = (int) $this->argument('count');
        $organization = Organization::create(['name' => 'Benchmark Org', 'code' => 'BENCH'.Str::upper(Str::random(5)), 'slug' => 'bench-'.Str::random(6)]);
        $first = ['Rahim', 'Karim', 'Jamal', 'Nasrin', 'Fatema', 'Mohammad', 'Abdul', 'Sultana', 'Hasan', 'Ayesha', 'করিম', 'রহিম', 'ফাতেমা', 'নাসরিন'];
        $last = ['Uddin', 'Rahman', 'Hossain', 'Akter', 'Khan', 'Islam', 'Begum', 'Ahmed', 'চৌধুরী', 'হোসেন', 'খাতুন'];

        $this->info("Seeding $count patients...");
        $started = microtime(true);

        for ($offset = 0; $offset < $count; $offset += 2000) {
            $patients = $index = [];
            $batch = min(2000, $count - $offset);
            for ($i = 0; $i < $batch; $i++) {
                $n = $offset + $i;
                $patients[] = [
                    'organization_id' => $organization->id, 'public_id' => (string) Str::ulid(), 'enterprise_patient_no' => sprintf('BEN-%08d', $n),
                    'first_name' => $first[$n % count($first)], 'last_name' => $last[intdiv($n, 7) % count($last)],
                    'phone' => sprintf('017%08d', $n), 'national_identifier' => sprintf('19%011d', $n), 'status' => 'active',
                    'created_at' => now(), 'updated_at' => now(),
                ];
            }
            DB::table('patients')->insert($patients);

            $ids = DB::table('patients')->where('organization_id', $organization->id)->where('enterprise_patient_no', '>=', sprintf('BEN-%08d', $offset))
                ->where('enterprise_patient_no', '<', sprintf('BEN-%08d', $offset + $batch))->get(['id', 'first_name', 'last_name', 'phone', 'national_identifier', 'enterprise_patient_no']);
            foreach ($ids as $p) {
                foreach (Normalizer::nameTokens($p->first_name.' '.$p->last_name) as $t) {
                    $index[] = ['organization_id' => $organization->id, 'patient_id' => $p->id, 'kind' => 'name', 'term' => $t];
                    if ($ph = Normalizer::phonetic($t)) {
                        $index[] = ['organization_id' => $organization->id, 'patient_id' => $p->id, 'kind' => 'phonetic', 'term' => $ph];
                    }
                }
                $index[] = ['organization_id' => $organization->id, 'patient_id' => $p->id, 'kind' => 'phone', 'term' => Normalizer::phone($p->phone)];
                $index[] = ['organization_id' => $organization->id, 'patient_id' => $p->id, 'kind' => 'nid', 'term' => Normalizer::code($p->national_identifier)];
                $code = Normalizer::code($p->enterprise_patient_no);
                $index[] = ['organization_id' => $organization->id, 'patient_id' => $p->id, 'kind' => 'mrn', 'term' => $code];
                $index[] = ['organization_id' => $organization->id, 'patient_id' => $p->id, 'kind' => 'mrn', 'term' => ltrim(preg_replace('/\D+/', '', $code), '0')];
            }
            foreach (array_chunk($index, 5000) as $chunk) {
                DB::table('patient_search_index')->insert($chunk);
            }
        }
        $this->info(sprintf('Seeded in %.1fs (%d index rows).', microtime(true) - $started, DB::table('patient_search_index')->where('organization_id', $organization->id)->count()));

        $user = User::factory()->create();
        $user->organizations()->attach($organization->id, ['access_level' => 'admin']);
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'patients.view', 'guard_name' => 'web']);
        $user->givePermissionTo('patients.view');
        $tenant->setOrganizationId($organization->id);

        $queries = [
            'mobile' => fn () => sprintf('017%08d', random_int(0, $count - 1)),
            'mobile prefix' => fn () => '0171234',
            'MRN' => fn () => sprintf('BEN-%08d', random_int(0, $count - 1)),
            'MRN tail' => fn () => (string) random_int(1, $count - 1),
            'NID' => fn () => sprintf('19%011d', random_int(0, $count - 1)),
            'name' => fn () => 'rahim uddin',
            'name prefix' => fn () => 'moham',
            'bangla name' => fn () => 'করিম হোসেন',
            'sound-alike' => fn () => 'Muhammad',
        ];

        $worst = 0;
        foreach ($queries as $label => $make) {
            $times = [];
            for ($i = 0; $i < 30; $i++) {
                $term = $make();
                $t = microtime(true);
                $search->search($user, $term);
                $times[] = (microtime(true) - $t) * 1000;
            }
            sort($times);
            $p95 = $times[(int) floor(0.95 * (count($times) - 1))];
            $worst = max($worst, $p95);
            $this->line(sprintf('%-14s p50 %6.1f ms   p95 %6.1f ms', $label, $times[intdiv(count($times), 2)], $p95));
        }

        $this->{$worst <= 500 ? 'info' : 'error'}(sprintf('Worst p95 %.1f ms (target 500 ms).', $worst));

        if (! $this->option('keep')) {
            DB::table('patient_search_index')->where('organization_id', $organization->id)->delete();
            Patient::withoutGlobalScopes()->withTrashed()->where('organization_id', $organization->id)->forceDelete();
            $user->delete();
            $organization->forceDelete();
            $this->info('Benchmark data removed.');
        }

        return $worst <= 500 ? self::SUCCESS : self::FAILURE;
    }
}
