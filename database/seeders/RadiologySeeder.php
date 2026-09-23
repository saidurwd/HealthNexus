<?php

namespace Database\Seeders;

use App\Models\Billing\BillingCategory;
use App\Models\Billing\BillingItem;
use App\Models\Company;
use App\Models\Radiology\RadiologyBodyPart;
use App\Models\Radiology\RadiologyPacsServer;
use App\Models\Radiology\RadiologyProcedure;
use App\Models\Radiology\RadiologyProtocol;
use App\Models\Radiology\RadiologySection;
use Illuminate\Database\Seeder;

/**
 * Minimal viable radiology catalog per company: sections, body parts, a starter procedure
 * catalog (Chest X-Ray, CT Brain with non-contrast/contrast protocols, Ultrasound Abdomen),
 * each mapped to a clinically-chargeable BillingItem (clinical_event_type='radiology_procedure')
 * so the Clinical Order -> Radiology Order -> Billing Charge chain has something to exercise
 * end to end in dev/testing — mirrors BillingSeeder/LabSeeder's approach.
 */
class RadiologySeeder extends Seeder
{
    public function run(): void
    {
        Company::all()->each(function (Company $company) {
            $sections = $this->seedSections($company);
            $bodyParts = $this->seedBodyParts($company);
            $billingCategory = $this->seedBillingCategory($company);

            $this->seedChestXray($company, $sections, $bodyParts, $billingCategory);
            $this->seedCtBrain($company, $sections, $bodyParts, $billingCategory);
            $this->seedUltrasoundAbdomen($company, $sections, $bodyParts, $billingCategory);
            $this->seedPacsServer($company);
        });
    }

    private function seedSections(Company $company): array
    {
        $definitions = ['XR' => 'X-Ray', 'CT' => 'CT', 'MRI' => 'MRI', 'US' => 'Ultrasound'];
        $sections = [];

        foreach ($definitions as $code => $name) {
            $sections[$code] = RadiologySection::query()->firstOrCreate(
                ['company_id' => $company->id, 'branch_id' => null, 'code' => $code],
                ['name' => $name, 'is_active' => true],
            );
        }

        return $sections;
    }

    private function seedBodyParts(Company $company): array
    {
        $definitions = [
            'HEAD' => ['Head', false], 'BRAIN' => ['Brain', false], 'CHEST' => ['Chest', false],
            'ABDOMEN' => ['Abdomen', false], 'SPINE' => ['Spine', false],
            'SHOULDER' => ['Shoulder', true], 'KNEE' => ['Knee', true], 'HAND' => ['Hand', true],
        ];

        $bodyParts = [];

        foreach ($definitions as $code => [$name, $laterality]) {
            $bodyParts[$code] = RadiologyBodyPart::query()->firstOrCreate(
                ['company_id' => $company->id, 'branch_id' => null, 'code' => $code],
                ['name' => $name, 'laterality_applicable' => $laterality, 'is_active' => true],
            );
        }

        return $bodyParts;
    }

    private function seedBillingCategory(Company $company): BillingCategory
    {
        return BillingCategory::query()->firstOrCreate(
            ['company_id' => $company->id, 'branch_id' => null, 'code' => 'DIAG'],
            ['name' => 'Diagnostics', 'description' => 'Laboratory and diagnostic services', 'is_active' => true],
        );
    }

    private function seedBillingItemFor(Company $company, BillingCategory $billingCategory, RadiologyProcedure $procedure, string $price): void
    {
        BillingItem::query()->firstOrCreate(
            ['company_id' => $company->id, 'branch_id' => null, 'item_code' => 'RAD-'.$procedure->code],
            [
                'category_id' => $billingCategory->id,
                'item_type' => 'diagnostic',
                'name' => $procedure->name,
                'description' => $procedure->name,
                'unit' => 'procedure',
                'base_price' => $price,
                'is_taxable' => false,
                'is_clinically_chargeable' => true,
                'clinical_event_type' => 'radiology_procedure',
                'clinical_event_key' => $procedure->code,
                'is_active' => true,
            ],
        );
    }

    private function seedChestXray(Company $company, array $sections, array $bodyParts, BillingCategory $billingCategory): void
    {
        $procedure = RadiologyProcedure::query()->firstOrCreate(
            ['company_id' => $company->id, 'branch_id' => null, 'code' => 'XR-CHEST-PA'],
            [
                'section_id' => $sections['XR']->id,
                'body_part_id' => $bodyParts['CHEST']->id,
                'name' => 'Chest X-Ray PA',
                'modality_type' => 'XR',
                'duration_minutes' => 10,
                'turnaround_time_minutes' => 60,
                'is_active' => true,
            ],
        );

        $this->seedBillingItemFor($company, $billingCategory, $procedure, '400.00');
    }

    private function seedCtBrain(Company $company, array $sections, array $bodyParts, BillingCategory $billingCategory): void
    {
        $procedure = RadiologyProcedure::query()->firstOrCreate(
            ['company_id' => $company->id, 'branch_id' => null, 'code' => 'CT-BRAIN'],
            [
                'section_id' => $sections['CT']->id,
                'body_part_id' => $bodyParts['BRAIN']->id,
                'name' => 'CT Brain',
                'modality_type' => 'CT',
                'duration_minutes' => 20,
                'turnaround_time_minutes' => 120,
                'contrast_required' => false,
                'is_active' => true,
            ],
        );

        RadiologyProtocol::query()->firstOrCreate(
            ['procedure_id' => $procedure->id, 'code' => 'NON-CONTRAST'],
            ['name' => 'Non-Contrast', 'contrast_required' => false, 'is_active' => true],
        );

        RadiologyProtocol::query()->firstOrCreate(
            ['procedure_id' => $procedure->id, 'code' => 'CONTRAST'],
            ['name' => 'Contrast', 'contrast_required' => true, 'is_active' => true],
        );

        $this->seedBillingItemFor($company, $billingCategory, $procedure, '4500.00');
    }

    private function seedUltrasoundAbdomen(Company $company, array $sections, array $bodyParts, BillingCategory $billingCategory): void
    {
        $procedure = RadiologyProcedure::query()->firstOrCreate(
            ['company_id' => $company->id, 'branch_id' => null, 'code' => 'US-ABDOMEN'],
            [
                'section_id' => $sections['US']->id,
                'body_part_id' => $bodyParts['ABDOMEN']->id,
                'name' => 'Ultrasound Abdomen',
                'modality_type' => 'US',
                'duration_minutes' => 20,
                'turnaround_time_minutes' => 60,
                'preparation_required' => true,
                'preparation_instructions' => 'Fasting for 6 hours prior to the scan.',
                'is_active' => true,
            ],
        );

        $this->seedBillingItemFor($company, $billingCategory, $procedure, '1200.00');
    }

    private function seedPacsServer(Company $company): void
    {
        RadiologyPacsServer::query()->firstOrCreate(
            ['company_id' => $company->id, 'branch_id' => null, 'code' => 'DEFAULT'],
            [
                'name' => 'Default PACS (not connected)',
                'adapter_type' => 'null',
                'is_active' => false,
                'is_default' => true,
            ],
        );
    }
}
