<?php

namespace App\Services\Analytics;

use App\Models\Analytics\AnalyticsLoad;
use App\Models\Analytics\AnalyticsSource;
use App\Models\User;
use App\Services\Analytics\Loaders\Loader;
use App\Services\Clinical\Alerts\AlertRequest;
use App\Services\Clinical\Alerts\AlertService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Throwable;

/** Runs the loaders one by one and records each run. A failing loader is recorded and the next one still runs; its watermark stays where it was, so the next run repeats the work. */
class LoadRunner
{
    public function __construct(private readonly LoaderRegistry $registry, private readonly Warehouse $warehouse) {}

    /**
     * @param  list<string>|null  $only  loader keys; null = all
     * @return array<string, AnalyticsLoad>
     */
    public function run(?array $only = null, ?User $by = null): array
    {
        $out = [];
        foreach ($this->registry->all() as $loader) {
            if ($only === null || in_array($loader->key(), $only, true)) {
                $out[$loader->key()] = $this->runOne($loader, $by);
            }
        }

        return $out;
    }

    public function runOne(Loader $loader, ?User $by = null): AnalyticsLoad
    {
        $source = AnalyticsSource::query()->firstOrCreate(['key' => $loader->key()], ['label' => $loader->label(), 'kind' => $loader->kind(), 'mode' => $loader->mode(), 'active' => true]);
        if (! $source->active) {
            return AnalyticsLoad::create(['source_key' => $loader->key(), 'started_at' => now(), 'finished_at' => now(), 'status' => 'ok', 'error' => 'Switched off.']);
        }
        $run = AnalyticsLoad::create(['source_key' => $loader->key(), 'started_at' => now(), 'status' => 'running', 'watermark_from' => $source->watermark, 'triggered_by' => $by?->id]);
        try {
            $result = $loader->load($loader->mode() === 'snapshot' ? null : $source->watermark);
            $run->update(['status' => 'ok', 'finished_at' => now(), 'rows_read' => $result['read'], 'rows_written' => $result['written'], 'watermark_to' => $result['watermark']]);
            $source->update(['watermark' => $result['watermark'], 'last_run_at' => now(), 'last_status' => 'ok', 'rows_total' => $source->rows_total + $result['written']]);
        } catch (Throwable $e) {
            $run->update(['status' => 'failed', 'finished_at' => now(), 'error' => mb_substr($e->getMessage(), 0, 500)]);
            $source->update(['last_run_at' => now(), 'last_status' => 'failed']);
            report($e);
        }

        return $run->refresh();
    }

    /** Sources that have not loaded successfully for too long, or whose last run failed. @return Collection<int, AnalyticsSource> */
    public function problems(): Collection
    {
        $limit = CarbonImmutable::now()->subMinutes((int) config('analytics.load.stale_minutes', 180));

        return AnalyticsSource::query()->where('active', true)->get()->filter(fn (AnalyticsSource $s) => $s->last_status === 'failed' || $s->last_run_at === null || $s->last_run_at->lt($limit))->values();
    }

    /** Empty the warehouse tables and the watermarks, then load everything again. The warehouse holds nothing that cannot be rebuilt from the operational tables. @return array<string, AnalyticsLoad> */
    public function rebuild(?User $by = null): array
    {
        foreach ($this->registry->all() as $loader) {
            $table = (new \ReflectionMethod($loader, 'target'));
            $table->setAccessible(true);
            $this->warehouse->table($table->invoke($loader))->delete();
        }
        AnalyticsSource::query()->update(['watermark' => null, 'rows_total' => 0, 'last_status' => null]);

        return $this->run(null, $by);
    }

    public function alertStale(int $organizationId): void
    {
        $bad = $this->problems();
        if ($bad->isEmpty()) {
            return;
        }
        try {
            app(AlertService::class)->raise(new AlertRequest(organizationId: $organizationId, category: 'analytics', severity: 'warning', message: $bad->count().' warehouse load(s) failed or are out of date: '.$bad->pluck('key')->implode(', ').'.',
                sourceModule: 'analytics', sourceType: 'analytics_load', sourceId: 0, dedupKey: 'analytics-stale-'.$organizationId, ackRequired: false));
        } catch (Throwable) {
            // a report must never stop the check
        }
    }
}
