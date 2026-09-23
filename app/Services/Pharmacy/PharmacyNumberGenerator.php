<?php

namespace App\Services\Pharmacy;

use App\Models\Pharmacy\PharmacyCounter;
use App\Services\SettingsService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Concurrency-safe document numbering (order/dispensing/transfer/return numbers), mirroring
 * App\Services\Radiology\RadiologyNumberGenerator's locked-counter-row pattern. Deliberately not
 * modeled after the unsafe orderByDesc()->id+1 PatientNumberGenerator pattern.
 */
class PharmacyNumberGenerator
{
    public function __construct(private readonly SettingsService $settings) {}

    public function generate(int $companyId, ?int $branchId, string $documentType, string $prefix): string
    {
        return DB::transaction(function () use ($companyId, $branchId, $documentType, $prefix) {
            $attributes = [
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'document_type' => $documentType,
                'prefix' => $prefix,
            ];

            try {
                PharmacyCounter::query()->firstOrCreate($attributes, ['last_number' => 0]);
            } catch (QueryException) {
                // Lost a race to create the counter row — another concurrent transaction
                // already inserted it; fall through to the locked read below, which will now find it.
            }

            $counter = PharmacyCounter::query()->where($attributes)->lockForUpdate()->first();

            $nextNumber = $counter->last_number + 1;
            $counter->update(['last_number' => $nextNumber]);

            return $this->format($prefix, $nextNumber);
        });
    }

    public function generateOrderNumber(int $companyId, ?int $branchId): string
    {
        $prefix = $this->settings->get('pharmacy.order_prefix', 'RX');

        return $this->generate($companyId, $branchId, 'ORD', $prefix);
    }

    public function generateDispensingNumber(int $companyId, ?int $branchId): string
    {
        $prefix = $this->settings->get('pharmacy.dispensing_prefix', 'DSP');

        return $this->generate($companyId, $branchId, 'DSP', $prefix);
    }

    public function generateTransferNumber(int $companyId, ?int $branchId): string
    {
        $prefix = $this->settings->get('pharmacy.transfer_prefix', 'TRF');

        return $this->generate($companyId, $branchId, 'TRF', $prefix);
    }

    public function generateReturnNumber(int $companyId, ?int $branchId): string
    {
        $prefix = $this->settings->get('pharmacy.return_prefix', 'RTN');

        return $this->generate($companyId, $branchId, 'RTN', $prefix);
    }

    private function format(string $prefix, int $sequence): string
    {
        $format = $this->settings->get('pharmacy.order_number_format', '{PREFIX}-{YEAR}-{SEQ:8}');

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
