<?php

namespace App\Services\Ot;

use App\Models\Ot\OtCounter;
use App\Services\SettingsService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Concurrency-safe document numbering (surgery/specimen numbers) — own counter table, mirroring
 * IpdNumberGenerator/PharmacyNumberGenerator's per-domain convention rather than extending the
 * existing clinical_number_counters enum (which is hard-restricted to clinical_order/prescription).
 */
class OtNumberGenerator
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
                OtCounter::query()->firstOrCreate($attributes, ['last_number' => 0]);
            } catch (QueryException) {
                // Lost a race to create the counter row — fall through to the locked read below.
            }

            $counter = OtCounter::query()->where($attributes)->lockForUpdate()->first();

            $nextNumber = $counter->last_number + 1;
            $counter->update(['last_number' => $nextNumber]);

            return $this->format($prefix, $nextNumber, $formatKey, $defaultFormat);
        });
    }

    public function generateSurgeryNumber(int $organizationId, ?int $branchId): string
    {
        $prefix = $this->settings->get('ot.surgery_number_prefix', 'OT');

        return $this->generate($organizationId, $branchId, 'SUR', $prefix, 'ot.surgery_number_format', '{PREFIX}-{YEAR}-{SEQ:8}');
    }

    public function generateSpecimenNumber(int $organizationId, ?int $branchId): string
    {
        return $this->generate($organizationId, $branchId, 'SPC', 'SPC', 'ot.specimen_number_format', '{PREFIX}-{YEAR}-{SEQ:8}');
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
