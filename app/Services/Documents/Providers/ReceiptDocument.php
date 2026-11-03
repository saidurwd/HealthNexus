<?php

namespace App\Services\Documents\Providers;

use App\Contracts\Documents\DocumentProvider;
use App\Models\Billing\BillingReceipt;
use App\Services\Documents\DocumentSource;

class ReceiptDocument implements DocumentProvider
{
    public function type(): string
    {
        return 'receipt';
    }

    public function permission(): string
    {
        return 'billing.receipt.view';
    }

    public function paper(): string
    {
        return 'thermal80';
    }

    public function isFinancial(): bool
    {
        return true;
    }

    public function resolve(string $reference, int $organizationId): ?DocumentSource
    {
        $receipt = BillingReceipt::query()
            ->where('organization_id', $organizationId)->where('receipt_number', $reference)
            ->with(['payment.patient', 'payment.method', 'payment.receivedBy', 'invoice', 'branch'])->first();

        if (! $receipt) {
            return null;
        }

        $payment = $receipt->payment;

        return new DocumentSource(
            reference: $receipt->receipt_number,
            variables: [
                'receipt_number' => $receipt->receipt_number,
                'issued_at' => $receipt->issued_at?->timezone(config('documents.timezone'))->format('d M Y H:i'),
                'patient' => ['name' => $payment->patient?->full_name, 'number' => $payment->patient?->enterprise_patient_no],
                'invoice_number' => $receipt->invoice?->invoice_number,
                'payment_method' => $payment->method?->name,
                'currency' => $payment->currency,
                'amount' => number_format((float) $payment->amount, 2),
                'received_by' => $payment->receivedBy?->name,
            ],
            patientId: $payment->patient_id,
            branchId: $receipt->branch_id,
            isVoid: $receipt->isVoided(),
            // Verification payload carries the document number only — no patient data in the code.
            qrPayload: 'HN:RECEIPT:'.$receipt->receipt_number,
        );
    }
}
