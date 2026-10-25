<?php

namespace Database\Seeders;

use App\Models\Billing\BillingCategory;
use App\Models\Billing\BillingItem;
use App\Models\Organization;
use App\Models\Ot\OtComplex;
use App\Models\Ot\OtProcedure;
use App\Models\Ot\OtProcedureCategory;
use App\Models\Ot\OtTheatre;
use App\Models\Specialty;
use Illuminate\Database\Seeder;

/**
 * Minimal viable OT catalog per organization: one complex with two theatres (capabilities attached),
 * a handful of procedure categories/procedures across a couple of specialties (reusing/creating
 * the shared Specialty lookup, since no other seeder populates it yet), and the surgery/theatre/
 * anesthesia/surgeon-fee BillingItems — mirrors IpdSeeder's approach so the Surgery Request ->
 * Schedule -> Procedure -> Billing chain has something to exercise end to end in dev/testing.
 */
class OtSeeder extends Seeder
{
    public function run(): void
    {
        Organization::all()->each(function (Organization $organization) {
            $specialties = $this->seedSpecialties($organization);
            $categories = $this->seedProcedureCategories($organization);
            $billingCategory = $this->seedBillingCategory($organization);

            $this->seedComplexAndTheatres($organization);
            $this->seedProcedures($organization, $specialties, $categories);
            $this->seedBillingItems($organization, $billingCategory, $categories);
        });
    }

    private function seedSpecialties(Organization $organization): array
    {
        $definitions = ['GEN_SURG' => 'General Surgery', 'ORTHO' => 'Orthopedics', 'ENT' => 'ENT'];
        $specialties = [];

        foreach ($definitions as $code => $name) {
            $specialties[$code] = Specialty::query()->firstOrCreate(
                ['organization_id' => $organization->id, 'code' => $code],
                ['name' => $name, 'is_active' => true],
            );
        }

        return $specialties;
    }

    private function seedProcedureCategories(Organization $organization): array
    {
        $definitions = ['GENERAL' => 'General', 'ORTHOPEDIC' => 'Orthopedic', 'ENT' => 'ENT'];
        $categories = [];

        foreach ($definitions as $code => $name) {
            $categories[$code] = OtProcedureCategory::query()->firstOrCreate(
                ['organization_id' => $organization->id, 'branch_id' => null, 'code' => $code],
                ['name' => $name, 'is_active' => true],
            );
        }

        return $categories;
    }

    private function seedBillingCategory(Organization $organization): BillingCategory
    {
        return BillingCategory::query()->firstOrCreate(
            ['organization_id' => $organization->id, 'branch_id' => null, 'code' => 'OT'],
            ['name' => 'Surgery', 'description' => 'Operating theatre and surgical charges', 'is_active' => true],
        );
    }

    private function seedComplexAndTheatres(Organization $organization): void
    {
        $complex = OtComplex::query()->firstOrCreate(
            ['organization_id' => $organization->id, 'branch_id' => null, 'code' => 'MAIN-OT'],
            ['name' => 'Main OT Complex', 'is_active' => true],
        );

        foreach ([
            ['code' => 'OT-1', 'name' => 'Theatre 1', 'capabilities' => ['general', 'laparoscopic']],
            ['code' => 'OT-2', 'name' => 'Theatre 2', 'capabilities' => ['orthopedic', 'emergency']],
        ] as $definition) {
            $theatre = OtTheatre::query()->firstOrCreate(
                ['organization_id' => $organization->id, 'theatre_code' => $definition['code']],
                [
                    'branch_id' => null,
                    'complex_id' => $complex->id,
                    'name' => $definition['name'],
                    'capacity' => 1,
                    'emergency_capable' => in_array('emergency', $definition['capabilities'], true),
                    'status' => OtTheatre::STATUS_AVAILABLE,
                    'is_active' => true,
                ],
            );

            foreach ($definition['capabilities'] as $capability) {
                $theatre->capabilities()->firstOrCreate(['capability' => $capability]);
            }
        }
    }

    private function seedProcedures(Organization $organization, array $specialties, array $categories): void
    {
        foreach ([
            ['code' => 'APPX', 'name' => 'Appendectomy', 'specialty' => 'GEN_SURG', 'category' => 'GENERAL', 'duration' => 60, 'day_care' => false],
            ['code' => 'HERNIA', 'name' => 'Hernia Repair', 'specialty' => 'GEN_SURG', 'category' => 'GENERAL', 'duration' => 90, 'day_care' => true],
            ['code' => 'ARTHRO-KNEE', 'name' => 'Knee Arthroscopy', 'specialty' => 'ORTHO', 'category' => 'ORTHOPEDIC', 'duration' => 75, 'day_care' => true],
            ['code' => 'TONSIL', 'name' => 'Tonsillectomy', 'specialty' => 'ENT', 'category' => 'ENT', 'duration' => 45, 'day_care' => true],
        ] as $definition) {
            OtProcedure::query()->firstOrCreate(
                ['organization_id' => $organization->id, 'branch_id' => null, 'code' => $definition['code']],
                [
                    'category_id' => $categories[$definition['category']]->id,
                    'specialty_id' => $specialties[$definition['specialty']]->id,
                    'name' => $definition['name'],
                    'default_duration_minutes' => $definition['duration'],
                    'laterality_applicable' => in_array($definition['code'], ['HERNIA', 'ARTHRO-KNEE'], true),
                    'site_applicable' => in_array($definition['code'], ['HERNIA', 'ARTHRO-KNEE'], true),
                    'day_care_eligible' => $definition['day_care'],
                    'emergency_eligible' => $definition['code'] === 'APPX',
                    'requires_implant' => false,
                    'requires_blood' => false,
                    'requires_specimen' => in_array($definition['code'], ['APPX', 'TONSIL'], true),
                    'is_active' => true,
                ],
            );
        }
    }

    private function seedBillingItems(Organization $organization, BillingCategory $billingCategory, array $categories): void
    {
        foreach ($categories as $code => $category) {
            BillingItem::query()->firstOrCreate(
                ['organization_id' => $organization->id, 'branch_id' => null, 'item_code' => 'OT-PROC-'.$code],
                [
                    'category_id' => $billingCategory->id,
                    'item_type' => 'procedure_fee',
                    'name' => $category->name.' Procedure Fee',
                    'unit' => 'procedure',
                    'base_price' => '3000.00',
                    'is_taxable' => false,
                    'is_clinically_chargeable' => true,
                    'clinical_event_type' => 'ot_procedure_fee',
                    'clinical_event_key' => $code,
                    'is_active' => true,
                ],
            );
        }

        foreach ([
            ['code' => 'OT-THEATRE', 'type' => 'ot_theatre_usage', 'name' => 'Theatre Usage Fee', 'price' => '2000.00'],
            ['code' => 'OT-ANESTHESIA', 'type' => 'ot_anesthesia_fee', 'name' => 'Anesthesia Fee', 'price' => '1500.00'],
            ['code' => 'OT-SURGEON', 'type' => 'ot_surgeon_fee', 'name' => 'Surgeon Professional Fee', 'price' => '5000.00'],
            ['code' => 'OT-IMPLANT', 'type' => 'ot_implant', 'name' => 'Implant Charge (generic)', 'price' => '0.00'],
        ] as $definition) {
            BillingItem::query()->firstOrCreate(
                ['organization_id' => $organization->id, 'branch_id' => null, 'item_code' => $definition['code']],
                [
                    'category_id' => $billingCategory->id,
                    'item_type' => $definition['type'],
                    'name' => $definition['name'],
                    'unit' => 'each',
                    'base_price' => $definition['price'],
                    'is_taxable' => false,
                    'is_clinically_chargeable' => true,
                    'clinical_event_type' => $definition['type'],
                    'clinical_event_key' => 'general',
                    'is_active' => true,
                ],
            );
        }
    }
}
