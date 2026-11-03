<?php

namespace Database\Seeders;

use App\Models\Billing\BillingCategory;
use App\Models\Billing\BillingItem;
use App\Models\Icu\IcuProcedure;
use App\Models\Ipd\IpdBed;
use App\Models\Ipd\IpdRoom;
use App\Models\Ipd\IpdWard;
use App\Models\Organization;
use Illuminate\Database\Seeder;

/**
 * Minimal viable ICU catalog per organization: one ICU ward (ward_type=icu) with ICU-capable,
 * ventilator-capable beds, a short bedside-procedure catalog, and the bed-day/ventilator-day/
 * critical-care-service/procedure-fee BillingItems — mirrors OtSeeder's approach so the Admission
 * Request -> Episode -> Procedure -> Billing chain has something to exercise end to end.
 */
class IcuSeeder extends Seeder
{
    public function run(): void
    {
        Organization::all()->each(function (Organization $organization) {
            $billingCategory = $this->seedBillingCategory($organization);

            $this->seedWardAndBeds($organization);
            $this->seedProcedures($organization);
            $this->seedBillingItems($organization, $billingCategory);
        });
    }

    private function seedBillingCategory(Organization $organization): BillingCategory
    {
        return BillingCategory::query()->firstOrCreate(
            ['organization_id' => $organization->id, 'branch_id' => null, 'code' => 'ICU'],
            ['name' => 'Critical Care', 'description' => 'ICU bed, ventilation and critical-care service charges', 'is_active' => true],
        );
    }

    private function seedWardAndBeds(Organization $organization): void
    {
        $ward = IpdWard::query()->firstOrCreate(
            ['organization_id' => $organization->id, 'branch_id' => null, 'code' => 'MICU'],
            ['name' => 'Medical ICU', 'ward_type' => IpdWard::WARD_TYPE_ICU, 'capacity' => 4, 'isolation_capable' => true, 'is_active' => true],
        );

        $room = IpdRoom::query()->firstOrCreate(
            ['organization_id' => $organization->id, 'branch_id' => null, 'ward_id' => $ward->id, 'room_number' => 'MICU-BAY-1'],
            ['room_type' => 'icu', 'capacity' => 4, 'is_active' => true],
        );

        foreach (['MICU-01', 'MICU-02', 'MICU-03', 'MICU-04'] as $bedCode) {
            IpdBed::query()->firstOrCreate(
                ['organization_id' => $organization->id, 'bed_code' => $bedCode],
                [
                    'branch_id' => null,
                    'room_id' => $room->id,
                    'gender_type' => 'any',
                    'status' => IpdBed::STATUS_AVAILABLE,
                    'icu_capable' => true,
                    'ventilator_capable' => true,
                    'oxygen_available' => true,
                    'monitor_available' => true,
                    'is_active' => true,
                ],
            );
        }
    }

    private function seedProcedures(Organization $organization): void
    {
        foreach ([
            ['code' => 'ICU-INTUBATION', 'name' => 'Endotracheal Intubation', 'category' => 'airway'],
            ['code' => 'ICU-CVC', 'name' => 'Central Venous Catheter Insertion', 'category' => 'line'],
            ['code' => 'ICU-ART-LINE', 'name' => 'Arterial Line Insertion', 'category' => 'line'],
            ['code' => 'ICU-CHEST-TUBE', 'name' => 'Chest Tube Insertion', 'category' => 'drain'],
            ['code' => 'ICU-DIALYSIS', 'name' => 'Renal Replacement Therapy', 'category' => 'dialysis'],
        ] as $definition) {
            IcuProcedure::query()->firstOrCreate(
                ['organization_id' => $organization->id, 'branch_id' => null, 'code' => $definition['code']],
                ['name' => $definition['name'], 'category' => $definition['category'], 'is_active' => true],
            );
        }
    }

    private function seedBillingItems(Organization $organization, BillingCategory $billingCategory): void
    {
        foreach ([
            ['code' => 'ICU-BED-DAY', 'type' => 'icu_bed_day', 'name' => 'ICU Bed Day', 'price' => '15000.00'],
            ['code' => 'ICU-VENT-DAY', 'type' => 'icu_ventilator_day', 'name' => 'Ventilator Day', 'price' => '8000.00'],
            ['code' => 'ICU-CRITICAL-CARE', 'type' => 'icu_critical_care_service', 'name' => 'Critical Care Service Fee', 'price' => '5000.00'],
        ] as $definition) {
            BillingItem::query()->firstOrCreate(
                ['organization_id' => $organization->id, 'branch_id' => null, 'item_code' => $definition['code']],
                [
                    'category_id' => $billingCategory->id,
                    'item_type' => $definition['type'],
                    'name' => $definition['name'],
                    'unit' => 'day',
                    'base_price' => $definition['price'],
                    'is_taxable' => false,
                    'is_clinically_chargeable' => true,
                    'clinical_event_type' => $definition['type'],
                    'clinical_event_key' => 'general',
                    'is_active' => true,
                ],
            );
        }

        foreach (IcuProcedure::query()->where('organization_id', $organization->id)->get() as $procedure) {
            BillingItem::query()->firstOrCreate(
                ['organization_id' => $organization->id, 'branch_id' => null, 'item_code' => 'ICU-PROC-'.$procedure->code],
                [
                    'category_id' => $billingCategory->id,
                    'item_type' => 'icu_procedure_fee',
                    'name' => $procedure->name.' Fee',
                    'unit' => 'procedure',
                    'base_price' => '2500.00',
                    'is_taxable' => false,
                    'is_clinically_chargeable' => true,
                    'clinical_event_type' => 'icu_procedure_fee',
                    'clinical_event_key' => $procedure->code,
                    'is_active' => true,
                ],
            );
        }
    }
}
