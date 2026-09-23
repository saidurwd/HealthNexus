<?php

namespace Database\Seeders;

use App\Models\Billing\BillingCategory;
use App\Models\Billing\BillingItem;
use App\Models\Company;
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
 * Minimal viable pharmacy catalog per company: dosage forms, routes, a couple of generics/brands,
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

        Company::all()->each(function (Company $company) use ($stockService, $user) {
            $dosageForms = $this->seedDosageForms($company);
            $routes = $this->seedRoutes($company);
            $billingCategory = $this->seedBillingCategory($company);
            $store = $this->seedStore($company);

            $paracetamol = $this->seedGeneric($company, 'PARA', 'Paracetamol', 'Analgesic/Antipyretic');
            $napa = $this->seedBrand($company, 'NAPA', 'Napa', 'Beximco Pharmaceuticals', $paracetamol);
            $napaMedication = $this->seedMedication($company, 'NAPA-500-TAB', $paracetamol, $napa, $dosageForms['TAB'], $routes['ORAL'], 'Napa 500mg Tablet', '500', 'mg');
            $this->seedBillingItemFor($company, $billingCategory, $napaMedication, '5.00');

            $amoxicillin = $this->seedGeneric($company, 'AMOX', 'Amoxicillin', 'Penicillin antibiotic');
            $amoxMedication = $this->seedMedication($company, 'AMOX-500-CAP', $amoxicillin, null, $dosageForms['CAP'], $routes['ORAL'], 'Amoxicillin 500mg Capsule', '500', 'mg');
            $this->seedBillingItemFor($company, $billingCategory, $amoxMedication, '8.00');

            if ($user) {
                $this->seedBatch($stockService, $store, $napaMedication, $user);
                $this->seedBatch($stockService, $store, $amoxMedication, $user);
            }
        });
    }

    private function seedDosageForms(Company $company): array
    {
        $definitions = ['TAB' => 'Tablet', 'CAP' => 'Capsule', 'SYR' => 'Syrup', 'INJ' => 'Injection'];
        $forms = [];

        foreach ($definitions as $code => $name) {
            $forms[$code] = PharmacyDosageForm::query()->firstOrCreate(
                ['company_id' => $company->id, 'branch_id' => null, 'code' => $code],
                ['name' => $name, 'is_active' => true],
            );
        }

        return $forms;
    }

    private function seedRoutes(Company $company): array
    {
        $definitions = ['ORAL' => 'Oral', 'IV' => 'Intravenous', 'IM' => 'Intramuscular', 'TOP' => 'Topical'];
        $routes = [];

        foreach ($definitions as $code => $name) {
            $routes[$code] = PharmacyRoute::query()->firstOrCreate(
                ['company_id' => $company->id, 'branch_id' => null, 'code' => $code],
                ['name' => $name, 'is_active' => true],
            );
        }

        return $routes;
    }

    private function seedBillingCategory(Company $company): BillingCategory
    {
        return BillingCategory::query()->firstOrCreate(
            ['company_id' => $company->id, 'branch_id' => null, 'code' => 'PHARM'],
            ['name' => 'Pharmacy', 'description' => 'Dispensed medications', 'is_active' => true],
        );
    }

    private function seedStore(Company $company): PharmacyStore
    {
        return PharmacyStore::query()->firstOrCreate(
            ['company_id' => $company->id, 'branch_id' => null, 'code' => 'PH-MAIN'],
            ['name' => 'Main Pharmacy Store', 'store_type' => 'main', 'is_active' => true],
        );
    }

    private function seedGeneric(Company $company, string $code, string $name, string $therapeuticClass): PharmacyGeneric
    {
        return PharmacyGeneric::query()->firstOrCreate(
            ['company_id' => $company->id, 'branch_id' => null, 'code' => $code],
            ['generic_name' => $name, 'therapeutic_class' => $therapeuticClass, 'is_active' => true],
        );
    }

    private function seedBrand(Company $company, string $code, string $name, string $manufacturer, PharmacyGeneric $generic): PharmacyBrand
    {
        return PharmacyBrand::query()->firstOrCreate(
            ['company_id' => $company->id, 'branch_id' => null, 'code' => $code],
            ['name' => $name, 'manufacturer' => $manufacturer, 'generic_id' => $generic->id, 'is_active' => true],
        );
    }

    private function seedMedication(Company $company, string $code, PharmacyGeneric $generic, ?PharmacyBrand $brand, PharmacyDosageForm $dosageForm, PharmacyRoute $route, string $name, string $strength, string $strengthUnit): PharmacyMedication
    {
        return PharmacyMedication::query()->firstOrCreate(
            ['company_id' => $company->id, 'branch_id' => null, 'code' => $code],
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

    private function seedBillingItemFor(Company $company, BillingCategory $billingCategory, PharmacyMedication $medication, string $price): void
    {
        BillingItem::query()->firstOrCreate(
            ['company_id' => $company->id, 'branch_id' => null, 'item_code' => 'PH-'.$medication->code],
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
