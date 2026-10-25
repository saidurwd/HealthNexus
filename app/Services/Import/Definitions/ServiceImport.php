<?php

namespace App\Services\Import\Definitions;

use App\Models\Billing\BillingCategory;
use App\Models\Billing\BillingItem;
use App\Services\Import\ImportContext;
use Illuminate\Support\Facades\DB;

class ServiceImport extends BaseImport
{
    public function key(): string
    {
        return 'services';
    }

    public function title(): string
    {
        return 'Services and prices';
    }

    public function permission(): string
    {
        return 'import.services';
    }

    public function columns(): array
    {
        return [
            ['name' => 'item_code', 'required' => true, 'example' => 'OPD-CONS'],
            ['name' => 'name', 'required' => true, 'example' => 'OPD Consultation'],
            ['name' => 'category_code', 'required' => true, 'example' => 'CONSULT', 'help' => 'Must already exist as a billing category'],
            ['name' => 'base_price', 'required' => true, 'example' => '500', 'help' => 'Taka'],
            ['name' => 'item_type', 'required' => false, 'example' => 'service'],
            ['name' => 'unit', 'required' => false, 'example' => 'each'],
            ['name' => 'is_taxable', 'required' => false, 'example' => 'no'],
        ];
    }

    public function validate(array $raw, ImportContext $context): array
    {
        $errors = [];
        $categoryCode = $this->required($raw, 'category_code', $errors, 50);
        $category = $categoryCode ? BillingCategory::query()->where('organization_id', $context->organizationId)->where('code', $categoryCode)->where('is_active', true)->first() : null;
        $categoryCode && ! $category && $errors[] = "category_code [$categoryCode] does not exist. Create the billing category first.";

        $data = [
            'item_code' => $this->required($raw, 'item_code', $errors, 50),
            'name' => $this->required($raw, 'name', $errors),
            'category_id' => $category?->id,
            'base_price' => $this->number($raw, 'base_price', $errors, true, 0),
            'item_type' => $this->text($raw, 'item_type') ?? 'service',
            'unit' => $this->text($raw, 'unit') ?? 'each',
            'is_taxable' => $this->boolean($raw, 'is_taxable'),
        ];

        return ['data' => $data, 'errors' => $errors];
    }

    public function fileKey(array $data): ?string
    {
        return strtoupper($data['item_code']);
    }

    public function findExisting(array $data, ImportContext $context): ?string
    {
        $hit = BillingItem::query()->where('organization_id', $context->organizationId)->where('item_code', $data['item_code'])->first();

        return $hit ? "service {$hit->item_code} ({$hit->name})" : null;
    }

    public function commit(array $data, ImportContext $context): array
    {
        $item = BillingItem::create($data + [
            'organization_id' => $context->organizationId, 'branch_id' => null, 'is_clinically_chargeable' => false,
            'is_active' => true, 'created_by' => $context->user->id,
        ]);

        return ['type' => BillingItem::class, 'id' => $item->id];
    }

    public function rollback(string $type, int $id, ImportContext $context): string
    {
        return $this->removeOrDeactivate(BillingItem::class, $id, $context->organizationId, fn ($item) => DB::table('billing_charges')->where('billing_item_id', $item->id)->exists()
            || DB::table('billing_invoice_items')->where('billing_item_id', $item->id)->exists());
    }
}
