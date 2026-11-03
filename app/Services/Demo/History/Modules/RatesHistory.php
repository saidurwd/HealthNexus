<?php

namespace App\Services\Demo\History\Modules;

use App\Models\Billing\BillingItem;
use App\Models\Billing\BillingPriceList;
use App\Models\Billing\BillingPriceListItem;
use App\Services\Demo\History\DemoHistoryModule;
use App\Services\Demo\History\HistoryContext;

/**
 * Bed and cabin rates: a price list with a lower day-rate for corporate patients and for staff, and a higher one for a private cabin, on top of the standard bed-day prices the Billing catalogue already has. Prices are made
 * through Billing's own price list (the rate card only reads them). Runs after the Billing catalogue; nothing here draws random numbers.
 */
class RatesHistory implements DemoHistoryModule
{
    public function name(): string
    {
        return 'Bed and cabin rates';
    }

    public function module(): string
    {
        return 'rates';
    }

    public function run(HistoryContext $context): string
    {
        $org = $context->organization->id;
        $list = BillingPriceList::query()->firstOrCreate(['organization_id' => $org, 'branch_id' => null, 'code' => 'BED-CLASSES'], ['name' => 'Bed and cabin classes', 'currency' => 'BDT', 'status' => 'active', 'priority' => 50, 'effective_from' => $context->from->toDateString(), 'created_by' => $context->actor->id]);
        $made = 0;
        foreach (BillingItem::query()->where('organization_id', $org)->where('clinical_event_type', 'ipd_bed_day')->where('is_active', true)->orderBy('id')->get() as $item) {
            foreach (['corporate' => 0.85, 'staff' => 0.5] as $category => $factor) {
                $hash = hash('sha1', implode('|', [$item->id, 'null', 'null', 'null', 'null', $category]));
                $row = BillingPriceListItem::query()->firstOrCreate(['price_list_id' => $list->id, 'billing_item_id' => $item->id, 'scope_hash' => $hash],
                    ['patient_category' => $category, 'unit_price' => number_format(round((float) $item->base_price * $factor, -1), 2, '.', ''), 'discount_type' => 'none', 'discount_value' => 0, 'tax_included' => false, 'priority' => 100, 'effective_from' => $context->from->toDateString(), 'is_active' => true]);
                $row->wasRecentlyCreated && $made++;
            }
        }

        return sprintf('a price list of bed and cabin classes with %d category prices (corporate 85%%, staff 50%% of the standard day rate)', $made);
    }
}
