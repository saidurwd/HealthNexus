<?php

namespace App\Services\Numbering;

use App\Models\NumberSeries;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * The one allocator for human-readable numbers (guideline 14.2, H-02).
 *
 * Allocation runs in its own transaction (or joins the caller's) and takes a row lock on the series row,
 * so concurrent callers queue instead of racing. A rolled-back caller transaction releases its number
 * together with the lock, so gaps only appear when a number was handed out and its document discarded
 * after commit — number_allocations shows every number that was ever issued.
 *
 * Format tokens: {PREFIX} {YEAR} {YY} {MM} {DD} {SEQ:n}
 */
class NumberSeriesService
{
    public const PERIOD_NONE = 'none';

    public const PERIOD_YEAR = 'year';

    public const PERIOD_MONTH = 'month';

    public const PERIOD_DAY = 'day';

    public function next(
        string $series,
        int $organizationId,
        ?int $branchId = null,
        ?int $hospitalId = null,
        string $prefix = '',
        string $format = '{PREFIX}-{YEAR}-{SEQ:8}',
        string $period = self::PERIOD_NONE,
        ?int $userId = null,
    ): string {
        $periodKey = match ($period) {
            self::PERIOD_YEAR => now()->format('Y'),
            self::PERIOD_MONTH => now()->format('Ym'),
            self::PERIOD_DAY => now()->format('Ymd'),
            default => '',
        };

        $scope = [
            'organization_id' => $organizationId,
            'hospital_id' => $hospitalId ?? 0,
            'branch_id' => $branchId ?? 0,
            'series' => $series,
            'period_key' => $periodKey,
        ];

        return DB::transaction(function () use ($scope, $prefix, $format, $userId) {
            try {
                NumberSeries::query()->firstOrCreate($scope, ['last_value' => 0]);
            } catch (QueryException) {
                // Another transaction created the row first; the locked read below finds it.
            }

            $row = NumberSeries::query()->where($scope)->lockForUpdate()->firstOrFail();

            $value = $row->last_value + 1;
            $row->update(['last_value' => $value]);

            $number = $this->format($format, $prefix, $value);

            DB::table('number_allocations')->insert([
                'number_series_id' => $row->id,
                'value' => $value,
                'number' => $number,
                'allocated_by' => $userId ?? auth()->id(),
                'allocated_at' => now(),
            ]);

            return $number;
        });
    }

    public function format(string $format, string $prefix, int $sequence): string
    {
        return preg_replace_callback('/\{(PREFIX|YEAR|YY|MM|DD|SEQ)(?::(\d+))?\}/', fn (array $m) => match ($m[1]) {
            'PREFIX' => $prefix,
            'YEAR' => now()->format('Y'),
            'YY' => now()->format('y'),
            'MM' => now()->format('m'),
            'DD' => now()->format('d'),
            'SEQ' => str_pad((string) $sequence, isset($m[2]) ? (int) $m[2] : 6, '0', STR_PAD_LEFT),
        }, $format);
    }
}
