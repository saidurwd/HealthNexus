<?php

namespace App\Interop\Mappers;

use App\Models\Billing\BillingInvoice;
use App\Models\Billing\BillingInvoiceItem;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Model;

class InvoiceMapper extends Mapper
{
    public function type(): string
    {
        return 'Invoice';
    }

    public function sources(): array
    {
        return [['model' => BillingInvoice::class, 'entity' => 'BillingInvoice', 'patient' => fn ($q, $pid) => $q->where('patient_id', $pid)->whereIn('status', ['finalized', 'partially_paid', 'paid', 'cancelled', 'refunded']),
            'search' => ['status' => fn ($q, $v) => $q->whereIn('status', match ($v) {
                'issued' => ['finalized', 'partially_paid', 'paid'], 'cancelled' => ['cancelled'], default => ['__none__']
            })]]];
    }

    /** @param BillingInvoice $m */
    public function toFhir(Model $m): array
    {
        $cur = $m->currency ?: 'BDT';

        return array_filter($this->base($m) + [
            'identifier' => [['system' => 'urn:healthnexus:invoice', 'value' => $m->invoice_number]],
            'status' => match ($m->status) {
                'cancelled' => 'cancelled', 'draft' => 'draft', default => 'issued'
            },
            'subject' => $this->ref('Patient', Patient::class, $m->patient_id),
            'date' => $m->invoice_date ? substr((string) $m->getRawOriginal('invoice_date'), 0, 10) : null,
            'lineItem' => BillingInvoiceItem::query()->where('invoice_id', $m->id)->orderBy('id')->get()->map(fn ($l, $i) => ['sequence' => $i + 1, 'chargeItemCodeableConcept' => ['text' => $l->description],
                'priceComponent' => [['type' => 'base', 'amount' => ['value' => (float) $l->net_amount, 'currency' => $cur]]]])->all(),
            'totalNet' => ['value' => (float) $m->subtotal, 'currency' => $cur],
            'totalGross' => ['value' => (float) $m->grand_total, 'currency' => $cur],
            'paymentTerms' => 'Paid '.number_format((float) $m->paid_amount, 2, '.', '').', due '.number_format((float) $m->due_amount, 2, '.', ''),
        ], fn ($v) => $v !== null && $v !== []);
    }
}
