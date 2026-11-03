<?php

namespace App\Services\Billing;

use App\Services\Numbering\NumberSeriesService;
use App\Services\SettingsService;

/**
 * Invoice/payment/receipt/refund/adjustment numbers, delegated to the shared NumberSeriesService.
 */
class BillingNumberGenerator
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly NumberSeriesService $numbers,
    ) {}

    /** Allocated by the shared Numbering Service (H-02); the sequence is keyed on document type, not prefix. */
    public function generate(int $organizationId, ?int $branchId, string $documentType, string $prefix): string
    {
        return $this->numbers->next(
            series: 'billing.'.strtolower($documentType),
            organizationId: $organizationId,
            branchId: $branchId,
            prefix: $prefix,
            format: $this->settings->get('billing.invoice_number_format', '{PREFIX}-{YEAR}-{SEQ:8}'),
        );
    }

    public function generateInvoiceNumber(int $organizationId, ?int $branchId): string
    {
        return $this->generate($organizationId, $branchId, 'INV', $this->settings->get('billing.invoice_prefix', 'INV'));
    }

    public function generatePaymentNumber(int $organizationId, ?int $branchId): string
    {
        return $this->generate($organizationId, $branchId, 'PMT', $this->settings->get('billing.payment_prefix', 'PMT'));
    }

    public function generateReceiptNumber(int $organizationId, ?int $branchId): string
    {
        return $this->generate($organizationId, $branchId, 'RCT', $this->settings->get('billing.receipt_prefix', 'RCT'));
    }

    public function generateRefundNumber(int $organizationId, ?int $branchId): string
    {
        return $this->generate($organizationId, $branchId, 'RFD', $this->settings->get('billing.refund_prefix', 'RFD'));
    }

    public function generateAdjustmentNumber(int $organizationId, ?int $branchId): string
    {
        return $this->generate($organizationId, $branchId, 'ADJ', $this->settings->get('billing.adjustment_prefix', 'ADJ'));
    }
}
