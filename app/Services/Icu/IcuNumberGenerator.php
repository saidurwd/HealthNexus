<?php

namespace App\Services\Icu;

use App\Models\Icu\IcuCounter;
use App\Services\SettingsService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Concurrency-safe document numbering (ICU episode numbers) — own counter table, mirroring
 * OtNumberGenerator/IpdNumberGenerator's per-domain convention.
 */
class IcuNumberGenerator
{
    public function __construct(private readonly SettingsService $settings) {}

    public function generate(int $organizationId, ?int $branchId, string $documentType, string $prefix, string $formatKey, string $defaultFormat): string
    {
        return DB::transaction(function () use ($organizationId, $branchId, $documentType, $prefix, $formatKey, $defaultFormat) {
            $attributes = [
                'organization_id' => $organizationId,
                'branch_id' => $branchId,
                'document_type' => $documentType,
                'prefix' => $prefix,
            ];

            try {
                IcuCounter::query()->firstOrCreate($attributes, ['last_number' => 0]);
            } catch (QueryException) {
                // Lost a race to create the counter row — fall through to the locked read below.
            }

            $counter = IcuCounter::query()->where($attributes)->lockForUpdate()->first();

            $nextNumber = $counter->last_number + 1;
            $counter->update(['last_number' => $nextNumber]);

            return $this->format($prefix, $nextNumber, $formatKey, $defaultFormat);
        });
    }

    public function generateEpisodeNumber(int $organizationId, ?int $branchId): string
    {
        $prefix = $this->settings->get('icu.episode_number_prefix', 'ICU');

        return $this->generate($organizationId, $branchId, 'EPI', $prefix, 'icu.episode_number_format', '{PREFIX}-{YEAR}-{SEQ:8}');
    }

    private function format(string $prefix, int $sequence, string $formatKey, string $defaultFormat): string
    {
        $format = $this->settings->get($formatKey, $defaultFormat);

        return preg_replace_callback('/\{(PREFIX|YEAR|SEQ)(?::(\d+))?\}/', function (array $m) use ($prefix, $sequence) {
            return match ($m[1]) {
                'PREFIX' => $prefix,
                'YEAR' => now()->format('Y'),
                'SEQ' => str_pad((string) $sequence, isset($m[2]) ? (int) $m[2] : 6, '0', STR_PAD_LEFT),
                default => $m[0],
            };
        }, $format);
    }
}
