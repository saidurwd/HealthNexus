<?php

namespace App\Console\Commands\Ops;

use App\Models\Organization;
use App\Models\User;
use App\Services\Ops\OpsChecks;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

/**
 * The machine-checkable part of the go-live checklist (guideline Appendix F-4). Prints the people/legal items too, as boxes a human
 * must tick. Exit code 1 when any automatic check fails — the pilot is "no-go" until they are all green.
 */
class PilotReadinessCommand extends Command
{
    protected $signature = 'pilot:readiness';

    protected $description = 'Check the technical go-live items and list the ones a person must confirm';

    public function handle(OpsChecks $ops): int
    {
        $rows = [];
        $add = function (string $name, bool $ok, string $detail) use (&$rows) {
            $rows[] = [$name, $ok ? 'OK' : 'FAIL', $detail];
        };

        $production = app()->environment('production');
        $add('Debug mode off', ! config('app.debug'), config('app.debug') ? 'APP_DEBUG is true — it shows stack traces to users.' : 'APP_DEBUG is false.');
        $add('Environment is production', $production, 'APP_ENV='.config('app.env'));
        $add('Site served over HTTPS', str_starts_with((string) config('app.url'), 'https://'), 'APP_URL='.config('app.url'));
        $add('Application key set', filled(config('app.key')), filled(config('app.key')) ? 'Set.' : 'Run php artisan key:generate.');
        $add('Mail is real', ! in_array(config('mail.default'), ['log', 'array'], true), 'MAIL_MAILER='.config('mail.default'));
        $add('Backups are encrypted', filled(config('ops.backup.passphrase')), filled(config('ops.backup.passphrase')) ? 'BACKUP_PASSPHRASE is set.' : 'Set BACKUP_PASSPHRASE.');
        $add('Queue is not "sync"', config('queue.default') !== 'sync', 'QUEUE_CONNECTION='.config('queue.default'));
        $add('Sessions are secure', ! $production || config('session.secure'), 'SESSION_SECURE_COOKIE='.var_export(config('session.secure'), true));

        $pending = collect(Artisan::call('migrate:status', ['--pending' => true]) === 0 ? explode("\n", trim(Artisan::output())) : [])->filter(fn ($l) => str_contains($l, 'Pending'))->count();
        $add('Migrations up to date', $pending === 0, $pending === 0 ? 'No pending migrations.' : "$pending pending migration(s).");

        foreach ($ops->monitoring() as $check) {
            $add($check['name'], $check['status'] !== 'fail', $check['detail']);
        }

        $admins = User::query()->where('is_active', true)->whereHas('roles', fn ($q) => $q->whereIn('name', ['hospital_admin', 'super_admin']))->get();
        $add('Administrators use two-step login', $admins->isNotEmpty() && $admins->every(fn ($u) => $u->mfa_enabled), $admins->isEmpty() ? 'No active administrator found.' : $admins->where('mfa_enabled', false)->count().' administrator(s) without MFA.');
        $training = Organization::query()->where('settings->training', true)->count();
        $add('A training organization exists', $training > 0, $training ? "$training training organization(s)." : 'Run php artisan demo:build.');

        $this->table(['Item', 'Result', 'Detail'], $rows);

        $this->newLine();
        $this->line('A person must confirm (tick in the go/no-go sheet, docs/pilot/go-no-go.md):');
        foreach ([
            'Super-users named and trained · role cards printed · support contacts and escalation sheet',
            'Masters loaded and signed · legacy import reconciled · opening stock and balances signed · print templates approved (Bangla check)',
            'Hardware tested: PCs, printers (A4/A5/80 mm/labels), scanners, UPS · network and Wi-Fi coverage',
            'Penetration test done or booked with a fix plan',
            'Downtime forms printed and drilled · downtime census tested · break-glass log reviewed',
            'Critical alert path tested end to end (lab critical → notification → acknowledge)',
            'Consent texts and privacy notice approved · data-protection checklist reviewed by counsel',
            'Management sign-off · rollback plan (paper + export) · hypercare roster for the first two weeks',
        ] as $item) {
            $this->line('  [ ] '.$item);
        }

        $failed = collect($rows)->where(1, 'FAIL')->count();
        $failed === 0 ? $this->info('All automatic checks passed.') : $this->error("$failed automatic check(s) failed: NO-GO until fixed.");

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
