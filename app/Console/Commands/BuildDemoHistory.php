<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\Organization;
use App\Models\User;
use App\Services\Demo\DemoAccess;
use App\Services\Demo\History\DemoHistoryModule;
use App\Services\Demo\History\HistoryContext;
use App\Services\ModuleManager;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Gives an organization six months of history in every built module (through the modules' own services) and creates the test users by
 * module / permission / feature plus a super user (AI-RULES 16). Training and development data only: refuses production.
 */
class BuildDemoHistory extends Command
{
    protected $signature = 'demo:history {organization? : Organization code (default: the first organization)} {--months= : Months of history (default 6)}
        {--visits-per-day= : Outpatient visits per working day (default 8)} {--password= : Password for every demo login (generated if omitted)}
        {--only=* : Run only these history classes (short name, e.g. PatientsHistory)} {--skip-access : Do not create the test users} {--allow-production : Run even when APP_ENV=production}';

    protected $description = 'Create six months of demo history for every module, and test users by module, permission and feature';

    public function handle(): int
    {
        if (app()->isProduction() && ! $this->option('allow-production')) {
            $this->error('Refusing to create fake history in production.');

            return self::FAILURE;
        }

        $organization = $this->argument('organization') ? Organization::where('code', $this->argument('organization'))->first() : Organization::query()->orderBy('id')->first();
        if (! $organization) {
            $this->error('No such organization.');

            return self::FAILURE;
        }
        $branch = Branch::where('organization_id', $organization->id)->orderBy('id')->firstOrFail();
        $password = $this->option('password') ?: Str::password(14, symbols: false);

        $access = [];
        if (! $this->option('skip-access')) {
            $access = app(DemoAccess::class)->build($organization, $branch, $password);
            $this->info(count($access).' test users ready.');
        }

        $actor = User::where('email', 'like', 'superuser@%')->whereHas('organizations', fn ($q) => $q->where('organizations.id', $organization->id))->first()
            ?? User::whereHas('organizations', fn ($q) => $q->where('organizations.id', $organization->id))->orderBy('id')->firstOrFail();
        $actor->hasRole('super_admin') || $actor->assignRole('super_admin');

        $context = new HistoryContext(
            $organization, $branch, $actor,
            (int) ($this->option('months') ?: config('demo_history.months')), (int) ($this->option('visits-per-day') ?: config('demo_history.visits_per_day')),
        );
        $only = array_filter((array) $this->option('only'));
        $modules = app(ModuleManager::class);

        foreach (config('demo_history.modules') as $class) {
            /** @var DemoHistoryModule $history */
            $history = app($class);
            if ($only && ! in_array(class_basename($class), $only, true)) {
                continue;
            }
            if (! $modules->isEnabled($history->module(), $organization->id)) {
                $this->line(sprintf('  %-26s skipped (module %s is off)', $history->name(), $history->module()));

                continue;
            }
            $started = microtime(true);
            $this->line(sprintf('  %-26s %s  [%.1fs]', $history->name(), $history->run($context), microtime(true) - $started));
        }

        if ($access) {
            $this->newLine();
            $this->table(['Kind', 'Login', 'Roles'], array_map(fn ($r) => [$r['kind'], $r['email'], $r['roles']], $access));
            $this->line("Password for every demo login: $password   (shown once; training data only)");
        }

        return self::SUCCESS;
    }
}
