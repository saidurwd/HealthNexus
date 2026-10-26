<?php

namespace App\Services\Demo\History\Modules;

use App\Models\Laboratory\LabContainerType;
use App\Models\Laboratory\LabReferenceRange;
use App\Models\Laboratory\LabSection;
use App\Models\Laboratory\LabSpecimenType;
use App\Models\Laboratory\LabTest;
use App\Models\Laboratory\LabTestCategory;
use App\Models\Pharmacy\PharmacyDosageForm;
use App\Models\Pharmacy\PharmacyGeneric;
use App\Models\Pharmacy\PharmacyMedication;
use App\Models\Pharmacy\PharmacyRoute;
use App\Models\Pharmacy\PharmacyStore;
use App\Services\Demo\History\DemoHistoryModule;
use App\Services\Demo\History\HistoryContext;
use App\Services\Pharmacy\PharmacyStockService;

/**
 * Widens the starter catalogues so six months of orders look real: more laboratory tests (with reference ranges) and a drug list, with
 * stock received into the store month by month. Master data is created like the seeders do; stock goes through the Stock Ledger service.
 */
class CatalogHistory implements DemoHistoryModule
{
    /** code => [name, unit, low, high] */
    private const TESTS = [
        'RBS' => ['Random Blood Sugar', 'mg/dL', 70, 140], 'CREAT' => ['Serum Creatinine', 'mg/dL', 0.6, 1.3], 'UREA' => ['Blood Urea', 'mg/dL', 15, 40], 'ALT' => ['ALT (SGPT)', 'U/L', 7, 56],
        'AST' => ['AST (SGOT)', 'U/L', 10, 40], 'BIL' => ['Total Bilirubin', 'mg/dL', 0.2, 1.2], 'CHOL' => ['Total Cholesterol', 'mg/dL', 125, 200], 'TG' => ['Triglycerides', 'mg/dL', 50, 150],
        'NA' => ['Serum Sodium', 'mmol/L', 135, 145], 'K' => ['Serum Potassium', 'mmol/L', 3.5, 5.1], 'CRP' => ['C-Reactive Protein', 'mg/L', 0, 6], 'HBA1C' => ['HbA1c', '%', 4, 5.6],
    ];

    /** code => [generic, name, strength, unit, form, price-hint] */
    private const DRUGS = [
        ['PARA-500-TAB', 'Paracetamol', 'Paracetamol 500mg Tablet', '500', 'mg', 'TAB'], ['IBU-400-TAB', 'Ibuprofen', 'Ibuprofen 400mg Tablet', '400', 'mg', 'TAB'], ['OMEP-20-CAP', 'Omeprazole', 'Omeprazole 20mg Capsule', '20', 'mg', 'CAP'],
        ['AZI-500-TAB', 'Azithromycin', 'Azithromycin 500mg Tablet', '500', 'mg', 'TAB'], ['CIP-500-TAB', 'Ciprofloxacin', 'Ciprofloxacin 500mg Tablet', '500', 'mg', 'TAB'], ['MET-500-TAB', 'Metformin', 'Metformin 500mg Tablet', '500', 'mg', 'TAB'],
        ['AML-5-TAB', 'Amlodipine', 'Amlodipine 5mg Tablet', '5', 'mg', 'TAB'], ['ATO-20-TAB', 'Atorvastatin', 'Atorvastatin 20mg Tablet', '20', 'mg', 'TAB'], ['SAL-100-INH', 'Salbutamol', 'Salbutamol 100mcg Inhaler', '100', 'mcg', 'TAB'],
        ['CET-10-TAB', 'Cetirizine', 'Cetirizine 10mg Tablet', '10', 'mg', 'TAB'], ['ORS-SAC', 'Oral rehydration salt', 'ORS Sachet', '20', 'g', 'TAB'], ['CEF-500-CAP', 'Cefuroxime', 'Cefuroxime 500mg Capsule', '500', 'mg', 'CAP'],
        ['PRED-5-TAB', 'Prednisolone', 'Prednisolone 5mg Tablet', '5', 'mg', 'TAB'], ['FUR-40-TAB', 'Furosemide', 'Furosemide 40mg Tablet', '40', 'mg', 'TAB'], ['CEF-1G-INJ', 'Ceftriaxone', 'Ceftriaxone 1g Injection', '1', 'g', 'INJ'],
        ['NS-500-INJ', 'Sodium chloride', 'Normal Saline 500ml', '500', 'ml', 'INJ'],
    ];

    public function __construct(private readonly PharmacyStockService $stock) {}

    public function name(): string
    {
        return 'Catalogues and stock';
    }

    public function module(): string
    {
        return 'lab';
    }

    public function run(HistoryContext $context): string
    {
        $orgId = $context->organization->id;
        $tests = $this->tests($orgId);
        $drugs = $this->drugs($context);

        return "$tests laboratory tests and $drugs medicines added, stock received monthly";
    }

    private function tests(int $orgId): int
    {
        $section = LabSection::where('organization_id', $orgId)->where('code', 'BIOCHEM')->first();
        $category = LabTestCategory::where('organization_id', $orgId)->where('code', 'CHEM')->first();
        $specimen = LabSpecimenType::where('organization_id', $orgId)->where('code', 'SERUM')->first();
        $container = LabContainerType::where('organization_id', $orgId)->where('code', 'PLAIN')->first();
        if (! $section || ! $category || ! $specimen || ! $container) {
            return 0;
        }

        $added = 0;
        foreach (self::TESTS as $code => [$name, $unit, $low, $high]) {
            $test = LabTest::firstOrCreate(['organization_id' => $orgId, 'branch_id' => null, 'code' => $code], [
                'category_id' => $category->id, 'section_id' => $section->id, 'specimen_type_id' => $specimen->id, 'container_type_id' => $container->id,
                'name' => $name, 'test_type' => 'quantitative', 'unit' => $unit, 'is_active' => true,
            ]);
            $test->wasRecentlyCreated && $added++;
            LabReferenceRange::firstOrCreate(['test_id' => $test->id, 'gender' => 'any', 'age_min_years' => null, 'age_max_years' => null], ['unit' => $unit, 'low' => (string) $low, 'high' => (string) $high, 'pregnancy_status' => 'any', 'is_active' => true]);
        }

        return $added;
    }

    private function drugs(HistoryContext $context): int
    {
        $orgId = $context->organization->id;
        $store = PharmacyStore::where('organization_id', $orgId)->orderBy('id')->first();
        $route = PharmacyRoute::where('organization_id', $orgId)->where('code', 'ORAL')->first();
        if (! $store || ! $route) {
            return 0;
        }

        $added = 0;
        $medications = [];
        foreach (self::DRUGS as [$code, $genericName, $name, $strength, $unit, $form]) {
            $dosage = PharmacyDosageForm::where('organization_id', $orgId)->where('code', $form)->first();
            if (! $dosage) {
                continue;
            }
            $generic = PharmacyGeneric::firstOrCreate(['organization_id' => $orgId, 'branch_id' => null, 'code' => strtoupper(substr($genericName, 0, 6))], ['generic_name' => $genericName, 'therapeutic_class' => 'General', 'is_active' => true]);
            $medication = PharmacyMedication::firstOrCreate(['organization_id' => $orgId, 'branch_id' => null, 'code' => $code], [
                'generic_id' => $generic->id, 'dosage_form_id' => $dosage->id, 'route_id' => $route->id, 'name' => $name, 'strength' => $strength, 'strength_unit' => $unit,
                'dispensing_unit' => 'tablet', 'prescription_unit' => 'tablet', 'is_prescription_required' => true, 'is_active' => true,
            ]);
            $medication->wasRecentlyCreated && $added++;
            $medications[] = $medication;
        }

        // Stock arrives every month (a fresh batch of each medicine), so the ledger has history and the dispensing history can draw from it.
        foreach (range(0, $context->months - 1) as $month) {
            $when = $context->from->addMonths($month)->setTime(10, 0);
            foreach ($medications as $medication) {
                $context->at($when, fn () => $this->stock->receiveNewBatch($store, $medication, [
                    'batch_number' => sprintf('B%s-%s', $when->format('ym'), $medication->code), 'manufacturing_date' => $when->subMonths(2)->toDateString(),
                    'expiry_date' => $when->addMonths(20)->toDateString(), 'unit_cost' => '2.00', 'selling_price' => '5.00',
                ], 1500, $context->actor));
            }
        }

        return $added;
    }
}
