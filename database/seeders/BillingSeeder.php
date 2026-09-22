<?php

namespace Database\Seeders;

use App\Models\Billing\BillingCategory;
use App\Models\Billing\BillingItem;
use App\Models\Billing\BillingPaymentMethod;
use App\Models\Billing\BillingPriceList;
use App\Models\Billing\BillingPriceListItem;
use App\Models\Billing\BillingTaxCategory;
use App\Models\Company;
use Illuminate\Database\Seeder;

/**
 * Minimal viable billing catalog per company: categories, one tax category, standard payment
 * methods, a handful of items (including one clinically-chargeable mapping so the
 * Clinical->Billing listener has something to match in dev/testing), and a default price list.
 */
class BillingSeeder extends Seeder
{
    public function run(): void
    {
        Company::all()->each(function (Company $company) {
            $categories = $this->seedCategories($company);
            $taxCategory = $this->seedTaxCategory($company);
            $this->seedPaymentMethods($company);
            $items = $this->seedItems($company, $categories, $taxCategory);
            $this->seedPriceList($company, $items);
        });
    }

    private function seedCategories(Company $company): array
    {
        $definitions = [
            'CONSULT' => 'Consultation',
            'PROC' => 'Procedures',
            'DIAG' => 'Diagnostics',
            'ROOM' => 'Room & Nursing',
        ];

        $categories = [];

        foreach ($definitions as $code => $name) {
            $categories[$code] = BillingCategory::query()->firstOrCreate(
                ['company_id' => $company->id, 'branch_id' => null, 'code' => $code],
                ['name' => $name, 'description' => $name, 'is_active' => true],
            );
        }

        return $categories;
    }

    private function seedTaxCategory(Company $company): BillingTaxCategory
    {
        return BillingTaxCategory::query()->firstOrCreate(
            ['company_id' => $company->id, 'branch_id' => null, 'code' => 'VAT'],
            ['name' => 'VAT', 'rate' => '0.0000', 'is_inclusive' => false, 'is_active' => true],
        );
    }

    private function seedPaymentMethods(Company $company): void
    {
        $definitions = [
            ['code' => 'CASH', 'name' => 'Cash', 'type' => 'cash'],
            ['code' => 'CARD', 'name' => 'Card', 'type' => 'card'],
            ['code' => 'MFS', 'name' => 'Mobile Financial Service', 'type' => 'mobile_financial_service'],
            ['code' => 'BANK', 'name' => 'Bank Transfer', 'type' => 'bank_transfer'],
        ];

        foreach ($definitions as $definition) {
            BillingPaymentMethod::query()->firstOrCreate(
                ['company_id' => $company->id, 'branch_id' => null, 'code' => $definition['code']],
                ['name' => $definition['name'], 'type' => $definition['type'], 'is_active' => true],
            );
        }
    }

    /**
     * @return array<string, BillingItem>
     */
    private function seedItems(Company $company, array $categories, BillingTaxCategory $taxCategory): array
    {
        $definitions = [
            'OPD-CONSULT' => [
                'category' => 'CONSULT', 'name' => 'OPD Consultation Fee', 'item_type' => 'consultation',
                'base_price' => '500.00', 'is_clinically_chargeable' => true,
                'clinical_event_type' => 'encounter', 'clinical_event_key' => 'completed',
            ],
            'FOLLOWUP-CONSULT' => [
                'category' => 'CONSULT', 'name' => 'Follow-up Consultation', 'item_type' => 'consultation',
                'base_price' => '300.00',
            ],
            'REGISTRATION' => [
                'category' => 'CONSULT', 'name' => 'Registration Fee', 'item_type' => 'service',
                'base_price' => '100.00',
            ],
            'PROC-GENERAL' => [
                'category' => 'PROC', 'name' => 'General Procedure', 'item_type' => 'procedure',
                'base_price' => '1000.00',
            ],
            'ROOM-GENERAL' => [
                'category' => 'ROOM', 'name' => 'General Room Charge (per day)', 'item_type' => 'room',
                'base_price' => '1500.00',
            ],
        ];

        $items = [];

        foreach ($definitions as $code => $definition) {
            $items[$code] = BillingItem::query()->firstOrCreate(
                ['company_id' => $company->id, 'branch_id' => null, 'item_code' => $code],
                [
                    'category_id' => $categories[$definition['category']]->id,
                    'tax_category_id' => $taxCategory->id,
                    'item_type' => $definition['item_type'],
                    'name' => $definition['name'],
                    'description' => $definition['name'],
                    'unit' => 'visit',
                    'base_price' => $definition['base_price'],
                    'is_taxable' => false,
                    'is_clinically_chargeable' => $definition['is_clinically_chargeable'] ?? false,
                    'clinical_event_type' => $definition['clinical_event_type'] ?? null,
                    'clinical_event_key' => $definition['clinical_event_key'] ?? null,
                    'is_active' => true,
                ],
            );
        }

        return $items;
    }

    /**
     * @param  array<string, BillingItem>  $items
     */
    private function seedPriceList(Company $company, array $items): void
    {
        $priceList = BillingPriceList::query()->firstOrCreate(
            ['company_id' => $company->id, 'branch_id' => null, 'code' => 'DEFAULT'],
            [
                'name' => 'Default Price List',
                'currency' => 'BDT',
                'status' => 'active',
                'priority' => 100,
            ],
        );

        foreach ($items as $item) {
            $scopeHash = hash('sha1', implode('|', [$item->id, 'null', 'null', 'null', 'null', 'null']));

            BillingPriceListItem::query()->firstOrCreate(
                ['price_list_id' => $priceList->id, 'billing_item_id' => $item->id, 'scope_hash' => $scopeHash],
                [
                    'unit_price' => $item->base_price,
                    'discount_type' => 'none',
                    'discount_value' => '0.00',
                    'tax_included' => false,
                    'priority' => 100,
                    'is_active' => true,
                ],
            );
        }
    }
}
