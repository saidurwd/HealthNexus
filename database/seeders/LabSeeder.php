<?php

namespace Database\Seeders;

use App\Models\Billing\BillingCategory;
use App\Models\Billing\BillingItem;
use App\Models\Company;
use App\Models\Laboratory\LabContainerType;
use App\Models\Laboratory\LabCriticalValue;
use App\Models\Laboratory\LabPanel;
use App\Models\Laboratory\LabReferenceRange;
use App\Models\Laboratory\LabSection;
use App\Models\Laboratory\LabSpecimenType;
use App\Models\Laboratory\LabTest;
use App\Models\Laboratory\LabTestCategory;
use Illuminate\Database\Seeder;

/**
 * Minimal viable lab catalog per company: sections, specimen/container types, a starter test
 * catalog (a CBC panel + Fasting Blood Glucose with age/gender reference ranges and a critical
 * value pair), each mapped to a clinically-chargeable BillingItem (clinical_event_type='lab_test')
 * so the Clinical Order -> Lab Order -> Billing Charge chain has something to exercise end to
 * end in dev/testing — mirrors how BillingSeeder seeds one clinically-chargeable item for the
 * same reason.
 */
class LabSeeder extends Seeder
{
    public function run(): void
    {
        Company::all()->each(function (Company $company) {
            $sections = $this->seedSections($company);
            $specimenTypes = $this->seedSpecimenTypes($company);
            $containerTypes = $this->seedContainerTypes($company);
            $categories = $this->seedTestCategories($company);
            $billingCategory = $this->seedBillingCategory($company);

            $cbcTests = $this->seedCbcTests($company, $sections, $categories, $specimenTypes, $containerTypes, $billingCategory);
            $this->seedCbcPanel($company, $cbcTests);
            $this->seedFastingGlucose($company, $sections, $categories, $specimenTypes, $containerTypes, $billingCategory);
        });
    }

    private function seedSections(Company $company): array
    {
        $definitions = ['HEMA' => 'Hematology', 'BIOCHEM' => 'Biochemistry', 'MICRO' => 'Microbiology'];
        $sections = [];

        foreach ($definitions as $code => $name) {
            $sections[$code] = LabSection::query()->firstOrCreate(
                ['company_id' => $company->id, 'branch_id' => null, 'code' => $code],
                ['name' => $name, 'is_active' => true],
            );
        }

        return $sections;
    }

    private function seedSpecimenTypes(Company $company): array
    {
        $definitions = ['BLOOD' => 'Blood', 'SERUM' => 'Serum', 'URINE' => 'Urine'];
        $types = [];

        foreach ($definitions as $code => $name) {
            $types[$code] = LabSpecimenType::query()->firstOrCreate(
                ['company_id' => $company->id, 'branch_id' => null, 'code' => $code],
                ['name' => $name, 'is_active' => true],
            );
        }

        return $types;
    }

    private function seedContainerTypes(Company $company): array
    {
        $definitions = ['EDTA' => 'EDTA Tube', 'PLAIN' => 'Plain Tube', 'FLUORIDE' => 'Fluoride Tube'];
        $types = [];

        foreach ($definitions as $code => $name) {
            $types[$code] = LabContainerType::query()->firstOrCreate(
                ['company_id' => $company->id, 'branch_id' => null, 'code' => $code],
                ['name' => $name, 'is_active' => true],
            );
        }

        return $types;
    }

    private function seedTestCategories(Company $company): array
    {
        $definitions = ['HEMA' => 'Hematology', 'CHEM' => 'Clinical Chemistry'];
        $categories = [];

        foreach ($definitions as $code => $name) {
            $categories[$code] = LabTestCategory::query()->firstOrCreate(
                ['company_id' => $company->id, 'branch_id' => null, 'code' => $code],
                ['name' => $name, 'is_active' => true],
            );
        }

        return $categories;
    }

    private function seedBillingCategory(Company $company): BillingCategory
    {
        return BillingCategory::query()->firstOrCreate(
            ['company_id' => $company->id, 'branch_id' => null, 'code' => 'DIAG'],
            ['name' => 'Diagnostics', 'description' => 'Laboratory and diagnostic services', 'is_active' => true],
        );
    }

    private function seedBillingItemFor(Company $company, BillingCategory $billingCategory, LabTest $test, string $price): void
    {
        BillingItem::query()->firstOrCreate(
            ['company_id' => $company->id, 'branch_id' => null, 'item_code' => 'LAB-'.$test->code],
            [
                'category_id' => $billingCategory->id,
                'item_type' => 'diagnostic',
                'name' => $test->name,
                'description' => $test->name,
                'unit' => 'test',
                'base_price' => $price,
                'is_taxable' => false,
                'is_clinically_chargeable' => true,
                'clinical_event_type' => 'lab_test',
                'clinical_event_key' => $test->code,
                'is_active' => true,
            ],
        );
    }

    /**
     * @return array<string, LabTest>
     */
    private function seedCbcTests(Company $company, array $sections, array $categories, array $specimenTypes, array $containerTypes, BillingCategory $billingCategory): array
    {
        $definitions = [
            'HGB' => ['name' => 'Hemoglobin', 'unit' => 'g/dL', 'price' => '300.00'],
            'RBC' => ['name' => 'RBC Count', 'unit' => 'x10^6/uL', 'price' => '250.00'],
            'WBC' => ['name' => 'WBC Count', 'unit' => 'x10^3/uL', 'price' => '250.00'],
            'PLT' => ['name' => 'Platelet Count', 'unit' => 'x10^3/uL', 'price' => '250.00'],
            'HCT' => ['name' => 'Hematocrit', 'unit' => '%', 'price' => '250.00'],
            'MCV' => ['name' => 'MCV', 'unit' => 'fL', 'price' => '250.00'],
        ];

        $tests = [];

        foreach ($definitions as $code => $definition) {
            $test = LabTest::query()->firstOrCreate(
                ['company_id' => $company->id, 'branch_id' => null, 'code' => $code],
                [
                    'category_id' => $categories['HEMA']->id,
                    'section_id' => $sections['HEMA']->id,
                    'specimen_type_id' => $specimenTypes['BLOOD']->id,
                    'container_type_id' => $containerTypes['EDTA']->id,
                    'name' => $definition['name'],
                    'test_type' => 'quantitative',
                    'unit' => $definition['unit'],
                    'is_active' => true,
                ],
            );

            $this->seedBillingItemFor($company, $billingCategory, $test, $definition['price']);
            $tests[$code] = $test;
        }

        // Hemoglobin reference ranges are the classic age/gender-dependent example (spec §12).
        $hgb = $tests['HGB'];

        LabReferenceRange::query()->firstOrCreate(
            ['test_id' => $hgb->id, 'gender' => 'male', 'age_min_years' => 18, 'age_max_years' => null],
            ['unit' => 'g/dL', 'low' => '13.0', 'high' => '17.0', 'pregnancy_status' => 'any', 'is_active' => true],
        );

        LabReferenceRange::query()->firstOrCreate(
            ['test_id' => $hgb->id, 'gender' => 'female', 'age_min_years' => 18, 'age_max_years' => null],
            ['unit' => 'g/dL', 'low' => '12.0', 'high' => '15.0', 'pregnancy_status' => 'any', 'is_active' => true],
        );

        return $tests;
    }

    private function seedCbcPanel(Company $company, array $cbcTests): void
    {
        $panel = LabPanel::query()->firstOrCreate(
            ['company_id' => $company->id, 'branch_id' => null, 'code' => 'CBC'],
            ['name' => 'Complete Blood Count', 'is_active' => true],
        );

        if ($panel->items()->doesntExist()) {
            foreach (array_values($cbcTests) as $sequence => $test) {
                $panel->items()->create(['test_id' => $test->id, 'sequence' => $sequence]);
            }
        }
    }

    private function seedFastingGlucose(Company $company, array $sections, array $categories, array $specimenTypes, array $containerTypes, BillingCategory $billingCategory): void
    {
        $test = LabTest::query()->firstOrCreate(
            ['company_id' => $company->id, 'branch_id' => null, 'code' => 'FBS'],
            [
                'category_id' => $categories['CHEM']->id,
                'section_id' => $sections['BIOCHEM']->id,
                'specimen_type_id' => $specimenTypes['SERUM']->id,
                'container_type_id' => $containerTypes['FLUORIDE']->id,
                'name' => 'Fasting Blood Glucose',
                'test_type' => 'quantitative',
                'unit' => 'mg/dL',
                'fasting_required' => true,
                'is_active' => true,
            ],
        );

        $this->seedBillingItemFor($company, $billingCategory, $test, '200.00');

        LabReferenceRange::query()->firstOrCreate(
            ['test_id' => $test->id, 'gender' => 'any', 'age_min_years' => null, 'age_max_years' => null],
            ['unit' => 'mg/dL', 'low' => '70', 'high' => '100', 'pregnancy_status' => 'any', 'is_active' => true],
        );

        LabCriticalValue::query()->firstOrCreate(
            ['test_id' => $test->id],
            ['low_threshold' => '40', 'high_threshold' => '400', 'is_active' => true],
        );
    }
}
