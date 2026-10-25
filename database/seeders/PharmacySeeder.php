<?php

namespace Database\Seeders;

use App\Models\Billing\BillingCategory;
use App\Models\Billing\BillingItem;
use App\Models\Organization;
use App\Models\Pharmacy\PharmacyBrand;
use App\Models\Pharmacy\PharmacyDosageForm;
use App\Models\Pharmacy\PharmacyGeneric;
use App\Models\Pharmacy\PharmacyMedication;
use App\Models\Pharmacy\PharmacyRoute;
use App\Models\Pharmacy\PharmacyStore;
use App\Models\User;
use App\Services\Pharmacy\PharmacyStockService;
use Illuminate\Database\Seeder;

/**
 * Minimal viable pharmacy catalog per organization: dosage forms, routes, a couple of generics/brands,
 * a starter medication catalog (Napa 500mg Tablet, Amoxicillin 500mg Capsule) each mapped to a
 * clinically-chargeable BillingItem (clinical_event_type='pharmacy_medication'), one pharmacy
 * store, and one received batch per medication so dispensing has real stock to exercise end to
 * end — mirrors LabSeeder/RadiologySeeder's approach.
 */
class PharmacySeeder extends Seeder
{
    public function run(): void
    {
        $stockService = app(PharmacyStockService::class);
        $user = User::first();

        Organization::all()->each(function (Organization $organization) use ($stockService, $user) {
            $dosageForms = $this->seedDosageForms($organization);
            $routes = $this->seedRoutes($organization);
            $billingCategory = $this->seedBillingCategory($organization);
            $store = $this->seedStore($organization);

            $paracetamol = $this->seedGeneric($organization, 'PARA', 'Paracetamol', 'Analgesic/Antipyretic');
            $napa = $this->seedBrand($organization, 'NAPA', 'Napa', 'Beximco Pharmaceuticals', $paracetamol);
            $napaMedication = $this->seedMedication($organization, 'NAPA-500-TAB', $paracetamol, $napa, $dosageForms['TAB'], $routes['ORAL'], 'Napa 500mg Tablet', '500', 'mg');
            $this->seedBillingItemFor($organization, $billingCategory, $napaMedication, '5.00');

            $amoxicillin = $this->seedGeneric($organization, 'AMOX', 'Amoxicillin', 'Penicillin antibiotic');
            $amoxMedication = $this->seedMedication($organization, 'AMOX-500-CAP', $amoxicillin, null, $dosageForms['CAP'], $routes['ORAL'], 'Amoxicillin 500mg Capsule', '500', 'mg');
            $this->seedBillingItemFor($organization, $billingCategory, $amoxMedication, '8.00');

            if ($user) {
                $this->seedBatch($stockService, $store, $napaMedication, $user);
                $this->seedBatch($stockService, $store, $amoxMedication, $user);
            }
        });
    }

    private function seedDosageForms(Organization $organization): array
    {
        $definitions = ['TAB' => 'Tablet', 'CAP' => 'Capsule', 'SYR' => 'Syrup', 'INJ' => 'Injection'];
        $forms = [];

        foreach ($definitions as $code => $name) {
            $forms[$code] = PharmacyDosageForm::query()->firstOrCreate(
                ['organization_id' => $organization->id, 'branch_id' => null, 'code' => $code],
                ['name' => $name, 'is_active' => true],
            );
        }

        return $forms;
    }

    private function seedRoutes(Organization $organization): array
    {
        $definitions = ['ORAL' => 'Oral', 'IV' => 'Intravenous', 'IM' => 'Intramuscular', 'TOP' => 'Topical'];
        $routes = [];

        foreach ($definitions as $code => $name) {
            $routes[$code] = PharmacyRoute::query()->firstOrCreate(
                ['organization_id' => $organization->id, 'branch_id' => null, 'code' => $code],
                ['name' => $name, 'is_active' => true],
            );
        }

        return $routes;
    }

    private function seedBillingCategory(Organization $organization): BillingCategory
    {
        return BillingCategory::query()->firstOrCreate(
            ['organization_id' => $organization->id, 'branch_id' => null, 'code' => 'PHARM'],
            ['name' => 'Pharmacy', 'description' => 'Dispensed medications', 'is_active' => true],
        );
    }

    private function seedStore(Organization $organization): PharmacyStore
    {
        return PharmacyStore::query()->firstOrCreate(
            ['organization_id' => $organization->id, 'branch_id' => null, 'code' => 'PH-MAIN'],
            ['name' => 'Main Pharmacy Store', 'store_type' => 'main', 'is_active' => true],
        );
    }

    private function seedGeneric(Organization $organization, string $code, string $name, string $therapeuticClass): PharmacyGeneric
    {
        return PharmacyGeneric::query()->firstOrCreate(
            ['organization_id' => $organization->id, 'branch_id' => null, 'code' => $code],
            ['generic_name' => $name, 'therapeutic_class' => $therapeuticClass, 'is_active' => true],
        );
    }

    private function seedBrand(Organization $organization, string $code, string $name, string $manufacturer, PharmacyGeneric $generic): PharmacyBrand
    {
        return PharmacyBrand::query()->firstOrCreate(
            ['organization_id' => $organization->id, 'branch_id' => null, 'code' => $code],
            ['name' => $name, 'manufacturer' => $manufacturer, 'generic_id' => $generic->id, 'is_active' => true],
        );
    }

    private function seedMedication(Organization $organization, string $code, PharmacyGeneric $generic, ?PharmacyBrand $brand, PharmacyDosageForm $dosageForm, PharmacyRoute $route, string $name, string $strength, string $strengthUnit): PharmacyMedication
    {
        return PharmacyMedication::query()->firstOrCreate(
            ['organization_id' => $organization->id, 'branch_id' => null, 'code' => $code],
            [
                'generic_id' => $generic->id,
                'brand_id' => $brand?->id,
                'dosage_form_id' => $dosageForm->id,
                'route_id' => $route->id,
                'name' => $name,
                'strength' => $strength,
                'strength_unit' => $strengthUnit,
                'dispensing_unit' => 'tablet',
                'prescription_unit' => 'tablet',
                'is_prescription_required' => true,
                'is_active' => true,
            ],
        );
    }

    private function seedBillingItemFor(Organization $organization, BillingCategory $billingCategory, PharmacyMedication $medication, string $price): void
    {
        BillingItem::query()->firstOrCreate(
            ['organization_id' => $organization->id, 'branch_id' => null, 'item_code' => 'PH-'.$medication->code],
            [
                'category_id' => $billingCategory->id,
                'item_type' => 'medication',
                'name' => $medication->name,
                'description' => $medication->name,
                'unit' => $medication->dispensing_unit ?? 'unit',
                'base_price' => $price,
                'is_taxable' => false,
                'is_clinically_chargeable' => true,
                'clinical_event_type' => 'pharmacy_medication',
                'clinical_event_key' => $medication->code,
                'is_active' => true,
            ],
        );
    }

    private function seedBatch(PharmacyStockService $stockService, PharmacyStore $store, PharmacyMedication $medication, User $user): void
    {
        if ($medication->batches()->exists()) {
            return;
        }

        $stockService->receiveNewBatch($store, $medication, [
            'batch_number' => 'SEED-'.$medication->code,
            'manufacturing_date' => now()->subMonths(3)->toDateString(),
            'expiry_date' => now()->addYear()->toDateString(),
            'unit_cost' => '2.00',
            'selling_price' => '5.00',
        ], 500, $user, \App\Models\Pharmacy\PharmacyStockTransaction::TYPE_OPENING);
    }
}
