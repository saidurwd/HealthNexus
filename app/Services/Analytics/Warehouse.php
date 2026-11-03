<?php

namespace App\Services\Analytics;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * The door to the warehouse. All warehouse reads and writes go through here so that pointing the warehouse at a reporting replica is a single setting.
 * The pseudonym of a patient is made here with a key that is not stored in any database.
 */
class Warehouse
{
    public function connection(): ConnectionInterface
    {
        return DB::connection(config('analytics.connection') ?: null);
    }

    public function table(string $name): Builder
    {
        return $this->connection()->table($name);
    }

    /** A patient's pseudonym: HMAC of the id with a key kept outside the database. Without the key (and the operational id) it cannot be turned back into a person. */
    public function pseudonym(int $patientId, int $organizationId): string
    {
        $key = (string) (config('analytics.pseudonym_key') ?: config('app.key'));

        return hash_hmac('sha256', $organizationId.':'.$patientId, $key);
    }

    public static function dateKey(CarbonInterface|string|null $date): ?int
    {
        return $date === null || $date === '' ? null : (int) Carbon::parse($date)->format('Ymd');
    }

    /** Insert or update rows, splitting them so that no statement has too many parameters. @param list<array<string,mixed>> $rows @param list<string> $uniqueBy */
    public function upsert(string $table, array $rows, array $uniqueBy): int
    {
        if ($rows === []) {
            return 0;
        }
        $columns = array_keys($rows[0]);
        $perStatement = max(1, intdiv(900, count($columns)));
        $update = array_values(array_diff($columns, $uniqueBy));
        foreach (array_chunk($rows, $perStatement) as $chunk) {
            $this->table($table)->upsert($chunk, $uniqueBy, $update);
        }

        return count($rows);
    }

    public function isSeparate(): bool
    {
        return (bool) config('analytics.connection');
    }
}
