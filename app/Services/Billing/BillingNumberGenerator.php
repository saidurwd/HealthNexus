<?php

namespace App\Services\Billing;

use App\Models\Billing\BillingCounter;
use App\Services\SettingsService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Concurrency-safe document numbering (invoice/payment/receipt/refund/adjustment numbers),
 * backed by row-level locking on billing_counters. Deliberately not modeled after
 * App\Services\Patients\PatientNumberGenerator, which uses an unsafe orderByDesc()->id+1 read —
 * unacceptable for money-critical, uniquely-constrained document numbers.
 */
class BillingNumberGenerator
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
                BillingCounter::query()->firstOrCreate($attributes, ['last_number' => 0]);
            } catch (QueryException) {
                // Lost a race to create the counter row — another concurrent transaction
                // already inserted it (unique constraint on company/branch/type/prefix); fall
                // through to the locked read below, which will now find it.
            }

            $counter = BillingCounter::query()->where($attributes)->lockForUpdate()->first();

            $nextNumber = $counter->last_number + 1;
            $counter->update(['last_number' => $nextNumber]);

            return $this->format($prefix, $nextNumber);
        });
    }

    public function generateInvoiceNumber(int $companyId, ?int $branchId): string
    {
        return $this->generate($companyId, $branchId, 'INV', $this->settings->get('billing.invoice_prefix', 'INV'));
    }

    public function generatePaymentNumber(int $companyId, ?int $branchId): string
    {
        return $this->generate($companyId, $branchId, 'PMT', $this->settings->get('billing.payment_prefix', 'PMT'));
    }

    public function generateReceiptNumber(int $companyId, ?int $branchId): string
    {
        return $this->generate($companyId, $branchId, 'RCT', $this->settings->get('billing.receipt_prefix', 'RCT'));
    }

    public function generateRefundNumber(int $companyId, ?int $branchId): string
    {
        return $this->generate($companyId, $branchId, 'RFD', $this->settings->get('billing.refund_prefix', 'RFD'));
    }

    public function generateAdjustmentNumber(int $companyId, ?int $branchId): string
    {
        return $this->generate($companyId, $branchId, 'ADJ', $this->settings->get('billing.adjustment_prefix', 'ADJ'));
    }

    private function format(string $prefix, int $sequence): string
    {
        $format = $this->settings->get('billing.invoice_number_format', '{PREFIX}-{YEAR}-{SEQ:8}');

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
