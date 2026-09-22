<?php

namespace App\Services\Billing;

use App\Models\Billing\BillingItem;
use App\Models\Billing\BillingPriceList;
use App\Models\Billing\BillingPriceListItem;
use Illuminate\Support\Carbon;

/**
 * Resolves the unit price + discount to use for a billing item, given a scope context.
 * Read-only: it never writes to billing_price_list_items, which is how historical invoice
 * prices stay preserved even after the catalog changes.
 */
class BillingPricingService
{
    /**
     * @param  array{company_id:int,branch_id?:int|null,patient_category?:string|null,corporate_id?:int|null,insurance_policy_id?:int|null,provider_id?:int|null,department_id?:int|null,date?:string}  $context
     * @return array{unit_price:string,discount_type:string,discount_value:string,tax_included:bool,price_list_item_id:?int,source:string}
     */
    public function resolvePrice(BillingItem $item, array $context): array
    {
        $companyId = $context['company_id'];
        $branchId = $context['branch_id'] ?? null;
        $date = $context['date'] ?? Carbon::now()->toDateString();

        $priceListIds = BillingPriceList::query()
            ->forTenant($companyId, $branchId)
            ->where('status', 'active')
            ->where(fn ($q) => $q->whereNull('effective_from')->orWhere('effective_from', '<=', $date))
            ->where(fn ($q) => $q->whereNull('effective_to')->orWhere('effective_to', '>=', $date))
            ->orderBy('priority')
            ->pluck('id');

        if ($priceListIds->isNotEmpty()) {
            $candidates = BillingPriceListItem::query()
                ->whereIn('price_list_id', $priceListIds)
                ->where('billing_item_id', $item->id)
                ->where('is_active', true)
                ->where(fn ($q) => $q->whereNull('effective_from')->orWhere('effective_from', '<=', $date))
                ->where(fn ($q) => $q->whereNull('effective_to')->orWhere('effective_to', '>=', $date))
                ->get()
                ->filter(fn (BillingPriceListItem $row) => $this->matchesScope($row, $context));

            $best = $this->pickBest($candidates, $priceListIds);

            if ($best !== null) {
                return $this->fromPriceListItem($best);
            }
        }

        return $this->fromItemBasePrice($item);
    }

    private function matchesScope(BillingPriceListItem $row, array $context): bool
    {
        $checks = [
            'insurance_policy_id' => $row->insurance_policy_id,
            'corporate_id' => $row->corporate_id,
            'provider_id' => $row->provider_id,
            'department_id' => $row->department_id,
            'patient_category' => $row->patient_category,
        ];

        foreach ($checks as $key => $value) {
            if ($value !== null && $value != ($context[$key] ?? null)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Resolution order (most specific wins): insurance policy > corporate > provider >
     * department > patient category > plain default. Weighted so a single more-specific
     * dimension always outranks any combination of less-specific ones, then ties break on the
     * owning price list's priority, then the row's own priority.
     */
    private function pickBest(iterable $candidates, \Illuminate\Support\Collection $priceListIds): ?BillingPriceListItem
    {
        $rankByPriceList = $priceListIds->flip();

        $best = null;
        $bestKey = null;

        foreach ($candidates as $row) {
            $key = [
                $rankByPriceList[$row->price_list_id] ?? PHP_INT_MAX,
                -$this->specificity($row),
                $row->priority,
            ];

            if ($best === null || $key < $bestKey) {
                $best = $row;
                $bestKey = $key;
            }
        }

        return $best;
    }

    private function specificity(BillingPriceListItem $row): int
    {
        $score = 0;
        $score += $row->insurance_policy_id !== null ? 16 : 0;
        $score += $row->corporate_id !== null ? 8 : 0;
        $score += $row->provider_id !== null ? 4 : 0;
        $score += $row->department_id !== null ? 2 : 0;
        $score += $row->patient_category !== null ? 1 : 0;

        return $score;
    }

    private function fromPriceListItem(BillingPriceListItem $row): array
    {
        $unitPrice = (string) $row->unit_price;

        if ($row->minimum_price !== null && bccomp($unitPrice, (string) $row->minimum_price, 2) === -1) {
            $unitPrice = (string) $row->minimum_price;
        }

        if ($row->maximum_price !== null && bccomp($unitPrice, (string) $row->maximum_price, 2) === 1) {
            $unitPrice = (string) $row->maximum_price;
        }

        return [
            'unit_price' => $unitPrice,
            'discount_type' => $row->discount_type,
            'discount_value' => (string) $row->discount_value,
            'tax_included' => (bool) $row->tax_included,
            'price_list_item_id' => $row->id,
            'source' => 'price_list',
        ];
    }

    private function fromItemBasePrice(BillingItem $item): array
    {
        return [
            'unit_price' => (string) $item->base_price,
            'discount_type' => 'none',
            'discount_value' => '0.00',
            'tax_included' => false,
            'price_list_item_id' => null,
            'source' => 'item_base_price',
        ];
    }
}
