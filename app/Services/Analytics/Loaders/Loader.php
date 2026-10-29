<?php

namespace App\Services\Analytics\Loaders;

use App\Services\Analytics\Warehouse;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;

/**
 * Fills one warehouse table from the operational tables. An incremental loader reads the rows changed since its watermark (with a small overlap), maps them to warehouse rows and upserts them
 * chunk by chunk. The watermark moves only when the whole run succeeded, so a failed run starts again from the old place; the writes are upserts, so repeating them is harmless.
 */
abstract class Loader
{
    public function __construct(protected readonly Warehouse $warehouse, protected readonly PatientKeys $patients) {}

    abstract public function key(): string;

    abstract public function label(): string;

    /** dimension | fact */
    public function kind(): string
    {
        return 'fact';
    }

    /** incremental | snapshot */
    public function mode(): string
    {
        return 'incremental';
    }

    /** Warehouse table. */
    abstract protected function target(): string;

    /** @return list<string> */
    abstract protected function uniqueBy(): array;

    /** The operational query (default connection). It must select the rows' own columns, including `id`, and the watermark column. */
    protected function source(): Builder
    {
        throw new \LogicException('An incremental loader needs a source.');
    }

    /** The column the watermark follows (qualified), e.g. `encounters.updated_at`. */
    protected function watermarkColumn(): string
    {
        return 'updated_at';
    }

    /** The key the source is paged by (qualified). */
    protected function idColumn(): string
    {
        return 'id';
    }

    /** A timestamp watermark gets the overlap; an id watermark does not. */
    protected function watermarkIsTime(): bool
    {
        return true;
    }

    /** For facts that depend on child rows (results, items): rows dated in the last N days are read again each run, changed or not. 0 = off. */
    protected function windowDays(): int
    {
        return 0;
    }

    /** The (qualified) date or timestamp column the window looks at. */
    protected function windowColumn(): ?string
    {
        return null;
    }

    /** @param Collection<int,\stdClass> $rows @return list<array<string,mixed>> */
    protected function map(Collection $rows): array
    {
        return [];
    }

    protected function chunk(): int
    {
        return max(50, (int) config('analytics.load.chunk', 500));
    }

    /** Snapshot loaders do their own work. @return array{read:int,written:int,watermark:?string} */
    public function load(?string $watermark): array
    {
        $column = $this->watermarkColumn();
        $short = last(explode('.', $column));
        $from = $watermark;
        if ($watermark !== null && $this->watermarkIsTime()) {
            $from = CarbonImmutable::parse($watermark)->subHours((int) config('analytics.load.overlap_hours', 2))->toDateTimeString();
        }
        $months = (int) config('analytics.load.first_load_months', 0);
        if ($from === null && $months > 0 && $this->watermarkIsTime()) {
            $from = CarbonImmutable::now()->subMonths($months)->toDateTimeString();
        }
        $read = $written = 0;
        $max = $watermark;
        $cut = $this->windowDays() > 0 && $this->windowColumn() ? CarbonImmutable::now()->subDays($this->windowDays())->toDateString() : null;
        $this->source()->when($from !== null, fn ($q) => $q->where(fn ($w) => $w->where($column, '>', $from)->when($cut, fn ($o) => $o->orWhere($this->windowColumn(), '>=', $cut))))->chunkById($this->chunk(), function (Collection $rows) use (&$read, &$written, &$max, $short) {
            $written += $this->warehouse->upsert($this->target(), $this->stamp($this->map($rows)), $this->uniqueBy());
            $read += $rows->count();
            foreach ($rows as $r) {
                $v = $r->{$short} ?? null;
                $v = $v instanceof \DateTimeInterface ? CarbonImmutable::instance($v)->toDateTimeString() : ($v === null ? null : (string) $v);
                if ($v !== null && ($max === null || ($this->watermarkIsTime() ? strcmp($v, (string) $max) > 0 : (int) $v > (int) $max))) {
                    $max = $v;
                }
            }
        }, $this->idColumn(), 'id');

        return ['read' => $read, 'written' => $written, 'watermark' => $max];
    }

    /** @param list<array<string,mixed>> $rows @return list<array<string,mixed>> */
    protected function stamp(array $rows): array
    {
        $now = now()->toDateTimeString();
        $isFact = str_starts_with($this->target(), 'fact_');

        return array_map(fn ($r) => $isFact ? $r + ['loaded_at' => $now] : $r, $rows);
    }

    /** Patient keys (the version in force on each row's date) for a chunk of rows that have patient_id and organization_id. @param Collection<int,\stdClass> $rows @return list<int|null> */
    protected function patientKeys(Collection $rows, callable $dateOf): array
    {
        $entries = [];
        foreach ($rows->values() as $r) {
            $entries[] = [$r->patient_id ?? null, (int) $r->organization_id, $dateOf($r)];
        }

        return $this->patients->lookup($entries);
    }

    /** Minutes between two timestamps (null if either is missing, or the end is before the start). */
    protected function minutes(mixed $from, mixed $to): ?int
    {
        if (! $from || ! $to) {
            return null;
        }
        $m = (int) round(CarbonImmutable::parse($from)->diffInSeconds(CarbonImmutable::parse($to), false) / 60);

        return $m < 0 ? null : $m;
    }

    /** The hospital's local date of a stored (UTC) timestamp. */
    protected function localDay(mixed $timestamp): ?string
    {
        return $timestamp ? CarbonImmutable::parse($timestamp, 'UTC')->setTimezone((string) config('analytics.timezone', 'Asia/Dhaka'))->toDateString() : null;
    }

    protected function localHour(mixed $timestamp): ?int
    {
        return $timestamp ? (int) CarbonImmutable::parse($timestamp, 'UTC')->setTimezone((string) config('analytics.timezone', 'Asia/Dhaka'))->format('G') : null;
    }

    protected function day(mixed $value): ?string
    {
        return $value ? substr((string) ($value instanceof \DateTimeInterface ? $value->format('Y-m-d') : $value), 0, 10) : null;
    }
}
