<?php

namespace App\Services\Ops;

use App\Models\BackupRun;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Database backup and restore drill (guideline 21.4). MySQL/MariaDB only. The database password and the backup passphrase are passed
 * through the environment of the child process, never on the command line.
 */
class BackupService
{
    public function __construct(private readonly ShellRunner $shell) {}

    public function backup(): BackupRun
    {
        $config = config('ops.backup');
        $db = $this->connection();
        $passphrase = $config['passphrase'];
        $name = 'healthnexus-'.now()->format('Ymd-His').'.sql.gz'.($passphrase ? '.enc' : '');
        $relative = trim($config['directory'], '/').'/'.$name;
        $disk = Storage::disk($config['disk']);
        $disk->makeDirectory($config['directory']);
        $absolute = $disk->path($relative);

        $run = BackupRun::create(['type' => 'backup', 'status' => 'running', 'disk' => $config['disk'], 'path' => $relative, 'encrypted' => (bool) $passphrase, 'started_at' => now()]);

        $dump = sprintf(
            'mysqldump --single-transaction --quick --routines --triggers --no-tablespaces -h %s -P %s -u %s %s',
            escapeshellarg($db['host']), escapeshellarg((string) $db['port']), escapeshellarg($db['username']), escapeshellarg($db['database']),
        );
        $pipeline = $dump.' | gzip -9'.($passphrase ? ' | openssl enc -aes-256-cbc -pbkdf2 -salt -pass env:BACKUP_PASSPHRASE' : '').' > '.escapeshellarg($absolute);

        $result = $this->shell->run('set -o pipefail; '.$pipeline, ['MYSQL_PWD' => (string) $db['password'], 'BACKUP_PASSPHRASE' => (string) $passphrase]);

        if ($result['code'] !== 0 || ! file_exists($absolute) || filesize($absolute) < 100) {
            $run->update(['status' => 'failed', 'error' => mb_substr(trim($result['error']) ?: 'The dump produced no data.', 0, 500), 'finished_at' => now()]);
            file_exists($absolute) && @unlink($absolute);

            throw new RuntimeException('Backup failed: '.$run->error);
        }

        $run->update([
            'status' => 'succeeded', 'size_bytes' => filesize($absolute), 'checksum' => hash_file('sha256', $absolute),
            'details' => ['table_counts' => $this->tableCounts(), 'tables' => count($this->tables())], 'finished_at' => now(),
        ]);

        $this->prune();

        return $run->refresh();
    }

    /**
     * Restores a backup into a throw-away database, checks it against what was recorded when the backup was taken, and drops it.
     * A person still has to sign the drill off in the runbook (`signOff`).
     */
    public function restoreDrill(?BackupRun $backup = null): BackupRun
    {
        $backup ??= BackupRun::query()->where('type', 'backup')->where('status', 'succeeded')->latest('id')->first()
            ?? throw new RuntimeException('There is no successful backup to restore.');

        $db = $this->connection();
        $scratch = $db['database'].'_restore_drill';
        $path = Storage::disk($backup->disk)->path($backup->path);
        $run = BackupRun::create(['type' => 'restore_drill', 'status' => 'running', 'source_run_id' => $backup->id, 'started_at' => now()]);

        try {
            if (! file_exists($path)) {
                throw new RuntimeException('The backup file is missing: '.$backup->path);
            }
            if (hash_file('sha256', $path) !== $backup->checksum) {
                throw new RuntimeException('The backup file does not match its checksum; it is damaged or was changed.');
            }

            $mysql = sprintf('mysql -h %s -P %s -u %s', escapeshellarg($db['host']), escapeshellarg((string) $db['port']), escapeshellarg($db['username']));
            $env = ['MYSQL_PWD' => (string) $db['password'], 'BACKUP_PASSPHRASE' => (string) config('ops.backup.passphrase')];

            $this->mustRun($mysql.' -e '.escapeshellarg("DROP DATABASE IF EXISTS `$scratch`; CREATE DATABASE `$scratch`"), $env);

            $read = $backup->encrypted ? 'openssl enc -d -aes-256-cbc -pbkdf2 -pass env:BACKUP_PASSPHRASE < '.escapeshellarg($path).' | gunzip' : 'gunzip < '.escapeshellarg($path);
            $this->mustRun('set -o pipefail; '.$read.' | '.$mysql.' '.escapeshellarg($scratch), $env);

            $restoredTables = array_values(array_filter(array_map('trim', explode("\n", $this->query($mysql, $env, $scratch, 'SHOW TABLES')))));
            $expected = $backup->details['table_counts'] ?? [];
            $problems = [];
            $counts = [];

            foreach ($expected as $table => $count) {
                $restored = in_array($table, $restoredTables, true) ? (int) trim($this->query($mysql, $env, $scratch, "SELECT COUNT(*) FROM `$table`")) : null;
                $counts[$table] = ['backup' => $count, 'restored' => $restored];
                $restored !== $count && $problems[] = "$table: backup had $count rows, restore has ".($restored ?? 'no table');
            }
            count($restoredTables) !== ($backup->details['tables'] ?? count($restoredTables)) && $problems[] = 'table count differs: '.count($restoredTables).' restored vs '.$backup->details['tables'].' at backup time';

            $run->update([
                'status' => $problems === [] ? 'succeeded' : 'failed', 'error' => $problems ? implode('; ', $problems) : null,
                'details' => ['tables_restored' => count($restoredTables), 'counts' => $counts], 'finished_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $run->update(['status' => 'failed', 'error' => mb_substr($e->getMessage(), 0, 500), 'finished_at' => now()]);
        } finally {
            $this->shell->run(sprintf('mysql -h %s -P %s -u %s -e %s', escapeshellarg($db['host']), escapeshellarg((string) $db['port']), escapeshellarg($db['username']), escapeshellarg("DROP DATABASE IF EXISTS `$scratch`")), ['MYSQL_PWD' => (string) $db['password']]);
        }

        return $run->refresh();
    }

    public function signOff(BackupRun $drill, string $name): BackupRun
    {
        if ($drill->type !== 'restore_drill' || $drill->status !== 'succeeded') {
            throw new RuntimeException('Only a successful restore drill can be signed off.');
        }
        $drill->update(['signed_off_by' => $name, 'signed_off_at' => now()]);

        return $drill->refresh();
    }

    /** Removes backup files older than the retention period (the history rows stay). @return int files removed */
    public function prune(): int
    {
        $removed = 0;
        BackupRun::query()->where('type', 'backup')->where('status', 'succeeded')->whereNotNull('path')
            ->where('finished_at', '<', now()->subDays((int) config('ops.backup.keep_days')))
            ->each(function (BackupRun $run) use (&$removed) {
                Storage::disk($run->disk)->delete($run->path) && $removed++;
                $run->update(['path' => null]);
            });

        return $removed;
    }

    private function mustRun(string $command, array $env): void
    {
        $result = $this->shell->run($command, $env);
        if ($result['code'] !== 0) {
            throw new RuntimeException(trim($result['error']) ?: 'A restore step failed.');
        }
    }

    protected function connection(): array
    {
        $config = config('database.connections.'.config('database.default'));
        if (! in_array($config['driver'] ?? '', ['mysql', 'mariadb'], true)) {
            throw new RuntimeException('Backups are implemented for MySQL/MariaDB only (current driver: '.($config['driver'] ?? 'unknown').').');
        }

        return $config;
    }

    /** Tables and views of the CURRENT database only (SHOW TABLES semantics). @return list<string> */
    protected function tables(): array
    {
        return array_map(fn ($row) => (string) array_values((array) $row)[0], DB::select('select table_name from information_schema.tables where table_schema = database()'));
    }

    /** @return array<string, int> */
    protected function tableCounts(): array
    {
        $tables = $this->tables();

        return collect(config('ops.backup.check_tables'))->filter(fn ($t) => in_array($t, $tables, true))
            ->mapWithKeys(fn ($t) => [$t => (int) DB::table($t)->count()])->all();
    }

    private function query(string $mysql, array $env, string $database, string $sql): string
    {
        $result = $this->shell->run($mysql.' -N -B '.escapeshellarg($database).' -e '.escapeshellarg($sql), $env);
        if ($result['code'] !== 0) {
            throw new RuntimeException(trim($result['error']) ?: 'Could not read the restored database.');
        }

        return $result['output'];
    }
}
