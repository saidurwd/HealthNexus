<?php

namespace Tests\Feature\Billing;

use App\Models\Billing\BillingCategory;
use App\Models\Billing\BillingCorporate;
use App\Models\Billing\BillingItem;
use App\Models\Billing\BillingPriceList;
use App\Models\Billing\BillingPriceListItem;
use App\Services\Billing\BillingPricingService;

class BillingPricingServiceTest extends BillingTestCase
{
    private BillingPricingService $pricing;

    private BillingItem $item;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pricing = app(BillingPricingService::class);

        $category = BillingCategory::create([
            'company_id' => $this->company->id, 'branch_id' => null,
            'name' => 'Consultation', 'code' => 'CONSULT', 'is_active' => true,
        ]);

        $this->item = BillingItem::create([
            'company_id' => $this->company->id, 'branch_id' => null,
            'category_id' => $category->id, 'item_code' => 'OPD-1', 'item_type' => 'consultation',
            'name' => 'OPD Consultation', 'base_price' => '500.00', 'is_taxable' => false,
            'is_clinically_chargeable' => false, 'is_active' => true,
        ]);
    }

    public function test_falls_back_to_item_base_price_when_no_price_list_matches(): void
    {
        $resolution = $this->pricing->resolvePrice($this->item, ['company_id' => $this->company->id, 'branch_id' => $this->branch->id]);

        $this->assertSame('500.00', $resolution['unit_price']);
        $this->assertSame('item_base_price', $resolution['source']);
    }

    public function test_default_price_list_entry_overrides_base_price(): void
    {
        $priceList = $this->createPriceList();
        $this->createPriceListItem($priceList, '450.00');

        $resolution = $this->pricing->resolvePrice($this->item, ['company_id' => $this->company->id, 'branch_id' => $this->branch->id]);

        $this->assertSame('450.00', $resolution['unit_price']);
        $this->assertSame('price_list', $resolution['source']);
    }

    public function test_corporate_scoped_price_outranks_default_price(): void
    {
        $priceList = $this->createPriceList();
        $this->createPriceListItem($priceList, '450.00');

        $corporate = BillingCorporate::create([
            'company_id' => $this->company->id, 'branch_id' => null,
            'name' => 'ACME Corp', 'code' => 'ACME', 'credit_limit' => '0.00',
            'payment_terms_days' => 30, 'billing_cycle' => 'monthly', 'status' => 'active',
        ]);

        $this->createPriceListItem($priceList, '350.00', ['corporate_id' => $corporate->id]);

        $resolution = $this->pricing->resolvePrice($this->item, [
            'company_id' => $this->company->id, 'branch_id' => $this->branch->id, 'corporate_id' => $corporate->id,
        ]);

        $this->assertSame('350.00', $resolution['unit_price']);
    }

    public function test_expired_price_list_item_is_ignored(): void
    {
        $priceList = $this->createPriceList();
        $this->createPriceListItem($priceList, '999.00', [
            'effective_from' => now()->subYear()->toDateString(),
            'effective_to' => now()->subMonth()->toDateString(),
        ]);

        $resolution = $this->pricing->resolvePrice($this->item, ['company_id' => $this->company->id, 'branch_id' => $this->branch->id]);

        $this->assertSame('500.00', $resolution['unit_price']);
        $this->assertSame('item_base_price', $resolution['source']);
    }

    public function test_unit_price_is_clamped_to_minimum_price(): void
    {
        $priceList = $this->createPriceList();
        $this->createPriceListItem($priceList, '10.00', ['minimum_price' => '100.00']);

        $resolution = $this->pricing->resolvePrice($this->item, ['company_id' => $this->company->id, 'branch_id' => $this->branch->id]);

        $this->assertSame('100.00', $resolution['unit_price']);
    }

    private function createPriceList(): BillingPriceList
    {
        return BillingPriceList::create([
            'company_id' => $this->company->id, 'branch_id' => null,
            'name' => 'Default', 'code' => 'DEFAULT', 'currency' => 'BDT',
            'status' => 'active', 'priority' => 100,
        ]);
    }

    private function createPriceListItem(BillingPriceList $priceList, string $unitPrice, array $overrides = []): BillingPriceListItem
    {
        return BillingPriceListItem::create([
            'price_list_id' => $priceList->id,
            'billing_item_id' => $this->item->id,
            'scope_hash' => hash('sha1', uniqid('', true)),
            'unit_price' => $unitPrice,
            'discount_type' => 'none',
            'discount_value' => '0.00',
            'tax_included' => false,
            'priority' => 100,
            'is_active' => true,
            ...$overrides,
        ]);
    }
}
