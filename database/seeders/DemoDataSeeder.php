<?php

namespace Database\Seeders;

use App\Models\Organization;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

/**
 * Six months of history for every built module, and test users by module / permission / feature plus a super user (AI-RULES 16),
 * on the first organization. Run with `php artisan db:seed --class=DemoDataSeeder` or set SEED_DEMO_HISTORY=true before `migrate:fresh --seed`.
 * Development and training only: the command refuses production.
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::query()->orderBy('id')->first();
        if (! $organization) {
            $this->command?->warn('No organization to build demo history for — run the main seeders first.');

            return;
        }

        $exit = Artisan::call('demo:history', ['organization' => $organization->code, '--password' => env('DEMO_PASSWORD', 'password')], $this->command?->getOutput());

        if ($exit !== 0) {
            $this->command?->warn('Demo history was not built (see the message above).');
        }
    }
}
