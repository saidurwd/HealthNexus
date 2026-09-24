<?php

namespace Database\Seeders;

use App\Models\Billing\BillingCategory;
use App\Models\Billing\BillingItem;
use App\Models\Company;
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
 * Minimal viable IPD catalog per company: one building/floor, two wards (Medical, Surgical) each
 * with a couple of rooms and beds, bed types, admission types/sources, discharge dispositions,
 * and admission-fee/bed-day BillingItems per bed type — mirrors LabSeeder/RadiologySeeder/
 * PharmacySeeder's approach so the Admission -> Bed Allocation -> Billing chain has something to
 * exercise end to end in dev/testing.
 */
class IpdSeeder extends Seeder
{
    public function run(): void
    {
        Company::all()->each(function (Company $company) {
            $building = $this->seedBuilding($company);
            $floor = $this->seedFloor($company, $building);
            $bedTypes = $this->seedBedTypes($company);
            $this->seedAdmissionTypes($company);
            $this->seedAdmissionSources($company);
            $this->seedDischargeDispositions($company);
            $billingCategory = $this->seedBillingCategory($company);

            $this->seedAdmissionFeeBillingItem($company, $billingCategory);

            $this->seedWard($company, $floor, 'MED', 'Medical Ward', $bedTypes['GENERAL'], $billingCategory);
            $this->seedWard($company, $floor, 'SURG', 'Surgical Ward', $bedTypes['PRIVATE'], $billingCategory);
        });
    }

    private function seedBuilding(Company $company): IpdBuilding
    {
        return IpdBuilding::query()->firstOrCreate(
            ['company_id' => $company->id, 'branch_id' => null, 'code' => 'MAIN'],
            ['name' => 'Main Building', 'is_active' => true],
        );
    }

    private function seedFloor(Company $company, IpdBuilding $building): IpdFloor
    {
        return IpdFloor::query()->firstOrCreate(
            ['company_id' => $company->id, 'branch_id' => null, 'code' => 'F3'],
            ['name' => '3rd Floor', 'building_id' => $building->id, 'is_active' => true],
        );
    }

    private function seedBedTypes(Company $company): array
    {
        $definitions = ['GENERAL' => 'General', 'PRIVATE' => 'Private', 'ICU' => 'ICU'];
        $types = [];

        foreach ($definitions as $code => $name) {
            $types[$code] = IpdBedType::query()->firstOrCreate(
                ['company_id' => $company->id, 'branch_id' => null, 'code' => $code],
                ['name' => $name, 'is_active' => true],
            );
        }

        return $types;
    }

    private function seedAdmissionTypes(Company $company): void
    {
        $definitions = ['ELECTIVE' => 'Elective', 'EMERGENCY' => 'Emergency', 'DAY_CARE' => 'Day Care'];

        foreach ($definitions as $code => $name) {
            IpdAdmissionType::query()->firstOrCreate(
                ['company_id' => $company->id, 'branch_id' => null, 'code' => $code],
                ['name' => $name, 'is_active' => true],
            );
        }
    }

    private function seedAdmissionSources(Company $company): void
    {
        $definitions = ['OPD' => 'OPD', 'EMERGENCY' => 'Emergency', 'REFERRAL' => 'Referral', 'DIRECT' => 'Direct'];

        foreach ($definitions as $code => $name) {
            IpdAdmissionSource::query()->firstOrCreate(
                ['company_id' => $company->id, 'branch_id' => null, 'code' => $code],
                ['name' => $name, 'is_active' => true],
            );
        }
    }

    private function seedDischargeDispositions(Company $company): void
    {
        $definitions = ['HOME' => 'Home', 'ANOTHER_HOSPITAL' => 'Another Hospital', 'DECEASED' => 'Deceased'];

        foreach ($definitions as $code => $name) {
            IpdDischargeDisposition::query()->firstOrCreate(
                ['company_id' => $company->id, 'branch_id' => null, 'code' => $code],
                ['name' => $name, 'is_active' => true],
            );
        }
    }

    private function seedBillingCategory(Company $company): BillingCategory
    {
        return BillingCategory::query()->firstOrCreate(
            ['company_id' => $company->id, 'branch_id' => null, 'code' => 'IPD'],
            ['name' => 'Inpatient', 'description' => 'Admission and bed occupancy charges', 'is_active' => true],
        );
    }

    private function seedAdmissionFeeBillingItem(Company $company, BillingCategory $billingCategory): void
    {
        foreach (['ELECTIVE', 'EMERGENCY', 'DAY_CARE', 'general'] as $code) {
            BillingItem::query()->firstOrCreate(
                ['company_id' => $company->id, 'branch_id' => null, 'item_code' => 'IPD-ADM-'.$code],
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

    private function seedBedDayBillingItem(Company $company, BillingCategory $billingCategory, IpdBedType $bedType, string $price): void
    {
        BillingItem::query()->firstOrCreate(
            ['company_id' => $company->id, 'branch_id' => null, 'item_code' => 'IPD-BED-'.$bedType->code],
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

    private function seedWard(Company $company, IpdFloor $floor, string $code, string $name, IpdBedType $bedType, BillingCategory $billingCategory): void
    {
        $this->seedBedDayBillingItem($company, $billingCategory, $bedType, $bedType->code === 'PRIVATE' ? '2500.00' : '1200.00');

        $ward = IpdWard::query()->firstOrCreate(
            ['company_id' => $company->id, 'branch_id' => null, 'code' => $code],
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
                    'company_id' => $company->id,
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
                    ['company_id' => $company->id, 'bed_code' => $code.'-'.$room->room_number.'-'.$bedLetter],
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
