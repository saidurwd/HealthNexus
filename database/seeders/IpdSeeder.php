<?php

namespace Database\Seeders;

use App\Models\Billing\BillingCategory;
use App\Models\Billing\BillingItem;
use App\Models\Organization;
use App\Models\Ipd\IpdAdmissionSource;
use App\Models\Ipd\IpdAdmissionType;
use App\Models\Ipd\IpdBed;
use App\Models\Ipd\IpdBedType;
use App\Models\Ipd\IpdBuilding;
use App\Models\Ipd\IpdDischargeDisposition;
use App\Models\Ipd\IpdFloor;
use App\Models\Ipd\IpdRoom;
use App\Models\Ipd\IpdWard;
use Illuminate\Database\Seeder;

/**
 * Minimal viable IPD catalog per organization: one building/floor, two wards (Medical, Surgical) each
 * with a couple of rooms and beds, bed types, admission types/sources, discharge dispositions,
 * and admission-fee/bed-day BillingItems per bed type — mirrors LabSeeder/RadiologySeeder/
 * PharmacySeeder's approach so the Admission -> Bed Allocation -> Billing chain has something to
 * exercise end to end in dev/testing.
 */
class IpdSeeder extends Seeder
{
    public function run(): void
    {
        Organization::all()->each(function (Organization $organization) {
            $building = $this->seedBuilding($organization);
            $floor = $this->seedFloor($organization, $building);
            $bedTypes = $this->seedBedTypes($organization);
            $this->seedAdmissionTypes($organization);
            $this->seedAdmissionSources($organization);
            $this->seedDischargeDispositions($organization);
            $billingCategory = $this->seedBillingCategory($organization);

            $this->seedAdmissionFeeBillingItem($organization, $billingCategory);

            $this->seedWard($organization, $floor, 'MED', 'Medical Ward', $bedTypes['GENERAL'], $billingCategory);
            $this->seedWard($organization, $floor, 'SURG', 'Surgical Ward', $bedTypes['PRIVATE'], $billingCategory);
        });
    }

    private function seedBuilding(Organization $organization): IpdBuilding
    {
        return IpdBuilding::query()->firstOrCreate(
            ['organization_id' => $organization->id, 'branch_id' => null, 'code' => 'MAIN'],
            ['name' => 'Main Building', 'is_active' => true],
        );
    }

    private function seedFloor(Organization $organization, IpdBuilding $building): IpdFloor
    {
        return IpdFloor::query()->firstOrCreate(
            ['organization_id' => $organization->id, 'branch_id' => null, 'code' => 'F3'],
            ['name' => '3rd Floor', 'building_id' => $building->id, 'is_active' => true],
        );
    }

    private function seedBedTypes(Organization $organization): array
    {
        $definitions = ['GENERAL' => 'General', 'PRIVATE' => 'Private', 'ICU' => 'ICU'];
        $types = [];

        foreach ($definitions as $code => $name) {
            $types[$code] = IpdBedType::query()->firstOrCreate(
                ['organization_id' => $organization->id, 'branch_id' => null, 'code' => $code],
                ['name' => $name, 'is_active' => true],
            );
        }

        return $types;
    }

    private function seedAdmissionTypes(Organization $organization): void
    {
        $definitions = ['ELECTIVE' => 'Elective', 'EMERGENCY' => 'Emergency', 'DAY_CARE' => 'Day Care'];

        foreach ($definitions as $code => $name) {
            IpdAdmissionType::query()->firstOrCreate(
                ['organization_id' => $organization->id, 'branch_id' => null, 'code' => $code],
                ['name' => $name, 'is_active' => true],
            );
        }
    }

    private function seedAdmissionSources(Organization $organization): void
    {
        $definitions = ['OPD' => 'OPD', 'EMERGENCY' => 'Emergency', 'REFERRAL' => 'Referral', 'DIRECT' => 'Direct'];

        foreach ($definitions as $code => $name) {
            IpdAdmissionSource::query()->firstOrCreate(
                ['organization_id' => $organization->id, 'branch_id' => null, 'code' => $code],
                ['name' => $name, 'is_active' => true],
            );
        }
    }

    private function seedDischargeDispositions(Organization $organization): void
    {
        $definitions = ['HOME' => 'Home', 'ANOTHER_HOSPITAL' => 'Another Hospital', 'DECEASED' => 'Deceased'];

        foreach ($definitions as $code => $name) {
            IpdDischargeDisposition::query()->firstOrCreate(
                ['organization_id' => $organization->id, 'branch_id' => null, 'code' => $code],
                ['name' => $name, 'is_active' => true],
            );
        }
    }

    private function seedBillingCategory(Organization $organization): BillingCategory
    {
        return BillingCategory::query()->firstOrCreate(
            ['organization_id' => $organization->id, 'branch_id' => null, 'code' => 'IPD'],
            ['name' => 'Inpatient', 'description' => 'Admission and bed occupancy charges', 'is_active' => true],
        );
    }

    private function seedAdmissionFeeBillingItem(Organization $organization, BillingCategory $billingCategory): void
    {
        foreach (['ELECTIVE', 'EMERGENCY', 'DAY_CARE', 'general'] as $code) {
            BillingItem::query()->firstOrCreate(
                ['organization_id' => $organization->id, 'branch_id' => null, 'item_code' => 'IPD-ADM-'.$code],
                [
                    'category_id' => $billingCategory->id,
                    'item_type' => 'admission_fee',
                    'name' => 'Admission Fee ('.$code.')',
                    'description' => 'One-time admission fee',
                    'unit' => 'admission',
                    'base_price' => '500.00',
                    'is_taxable' => false,
                    'is_clinically_chargeable' => true,
                    'clinical_event_type' => 'ipd_admission_fee',
                    'clinical_event_key' => $code,
                    'is_active' => true,
                ],
            );
        }
    }

    private function seedBedDayBillingItem(Organization $organization, BillingCategory $billingCategory, IpdBedType $bedType, string $price): void
    {
        BillingItem::query()->firstOrCreate(
            ['organization_id' => $organization->id, 'branch_id' => null, 'item_code' => 'IPD-BED-'.$bedType->code],
            [
                'category_id' => $billingCategory->id,
                'item_type' => 'bed_day',
                'name' => $bedType->name.' Bed — Daily Charge',
                'description' => 'Per-day bed occupancy charge',
                'unit' => 'day',
                'base_price' => $price,
                'is_taxable' => false,
                'is_clinically_chargeable' => true,
                'clinical_event_type' => 'ipd_bed_day',
                'clinical_event_key' => $bedType->code,
                'is_active' => true,
            ],
        );
    }

    private function seedWard(Organization $organization, IpdFloor $floor, string $code, string $name, IpdBedType $bedType, BillingCategory $billingCategory): void
    {
        $this->seedBedDayBillingItem($organization, $billingCategory, $bedType, $bedType->code === 'PRIVATE' ? '2500.00' : '1200.00');

        $ward = IpdWard::query()->firstOrCreate(
            ['organization_id' => $organization->id, 'branch_id' => null, 'code' => $code],
            [
                'name' => $name,
                'building_id' => $floor->building_id,
                'floor_id' => $floor->id,
                'gender_policy' => 'any',
                'capacity' => 4,
                'isolation_capable' => false,
                'is_active' => true,
            ],
        );

        for ($roomNumber = 1; $roomNumber <= 2; $roomNumber++) {
            $room = IpdRoom::query()->firstOrCreate(
                ['ward_id' => $ward->id, 'room_number' => (string) (100 * $roomNumber)],
                [
                    'organization_id' => $organization->id,
                    'branch_id' => null,
                    'room_type' => $bedType->code === 'PRIVATE' ? 'private' : 'general',
                    'capacity' => 2,
                    'gender_policy' => 'any',
                    'isolation_capable' => false,
                    'is_vip' => false,
                    'is_active' => true,
                ],
            );

            foreach (['A', 'B'] as $bedLetter) {
                IpdBed::query()->firstOrCreate(
                    ['organization_id' => $organization->id, 'bed_code' => $code.'-'.$room->room_number.'-'.$bedLetter],
                    [
                        'branch_id' => null,
                        'room_id' => $room->id,
                        'bed_type_id' => $bedType->id,
                        'bed_name' => 'Bed '.$bedLetter,
                        'gender_type' => 'any',
                        'status' => IpdBed::STATUS_AVAILABLE,
                        'is_active' => true,
                    ],
                );
            }
        }
    }
}
