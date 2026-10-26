<?php

namespace App\Services\Blood;

use App\Models\Billing\BillingCategory;
use App\Models\Billing\BillingItem;
use App\Models\Blood\BloodStorageLocation;
use App\Models\Laboratory\LabContainerType;
use App\Models\Laboratory\LabSection;
use App\Models\Laboratory\LabSpecimenType;
use App\Models\Laboratory\LabTest;
use App\Models\Laboratory\LabTestCategory;

/** The masters a blood bank needs, made idempotently per organization: storage locations, the laboratory tests it orders, and the price-list items it charges. */
class BloodMasterData
{
    public function __construct(private readonly BloodPolicy $policy) {}

    public function ensure(int $organizationId): void
    {
        $this->locations($organizationId);
        $this->labTests($organizationId);
        $this->billingItems($organizationId);
    }

    private function locations(int $orgId): void
    {
        foreach ([['BB-QUAR', 'Quarantine fridge', 'quarantine', 2, 6], ['BB-FRIDGE1', 'Blood fridge 1', 'fridge', 2, 6], ['BB-FRIDGE2', 'Blood fridge 2', 'fridge', 2, 6], ['BB-FREEZER1', 'Plasma freezer 1', 'freezer', -40, -25], ['BB-AGIT1', 'Platelet agitator 1', 'agitator', 20, 24]] as [$code, $name, $kind, $min, $max]) {
            BloodStorageLocation::firstOrCreate(['organization_id' => $orgId, 'code' => $code], ['name' => $name, 'kind' => $kind, 'min_temp_c' => $min, 'max_temp_c' => $max, 'is_active' => true]);
        }
    }

    private function labTests(int $orgId): void
    {
        $section = LabSection::firstOrCreate(['organization_id' => $orgId, 'branch_id' => null, 'code' => 'BB'], ['name' => 'Blood bank', 'is_active' => true]);
        $category = LabTestCategory::firstOrCreate(['organization_id' => $orgId, 'branch_id' => null, 'code' => 'BB'], ['name' => 'Blood bank', 'is_active' => true]);
        $specimen = LabSpecimenType::firstOrCreate(['organization_id' => $orgId, 'branch_id' => null, 'code' => 'BLOOD'], ['name' => 'Blood', 'is_active' => true]);
        $container = LabContainerType::firstOrCreate(['organization_id' => $orgId, 'branch_id' => null, 'code' => 'PLAIN'], ['name' => 'Plain Tube', 'is_active' => true]);

        $tests = $this->policy->get('screening_tests') + ['BB-ABO' => 'Blood group ABO', 'BB-RH' => 'Rh (D) type'];
        foreach ($tests as $code => $name) {
            LabTest::firstOrCreate(['organization_id' => $orgId, 'branch_id' => null, 'code' => $code], [
                'category_id' => $category->id, 'section_id' => $section->id, 'specimen_type_id' => $specimen->id, 'container_type_id' => $container->id,
                'name' => $name, 'test_type' => 'qualitative', 'is_active' => true,
            ]);
        }
    }

    private function billingItems(int $orgId): void
    {
        $category = BillingCategory::firstOrCreate(['organization_id' => $orgId, 'branch_id' => null, 'code' => 'BLOOD'], ['name' => 'Blood bank', 'is_active' => true]);
        $prices = ['BLD-WB' => 1500, 'BLD-PRBC' => 1500, 'BLD-FFP' => 800, 'BLD-PLT' => 3500, 'BLD-CRYO' => 1200, 'BLD-XM' => 300];
        foreach ($prices as $code => $price) {
            BillingItem::firstOrCreate(['organization_id' => $orgId, 'branch_id' => null, 'item_code' => $code], [
                'category_id' => $category->id, 'item_type' => 'service', 'name' => 'Blood bank — '.$code, 'unit' => 'unit', 'base_price' => number_format($price, 2, '.', ''),
                'is_taxable' => false, 'is_clinically_chargeable' => false, 'is_active' => true,
            ]);
        }
    }
}
