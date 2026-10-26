<?php

namespace App\Services\Demo\History\Modules;

use App\Models\Billing\BillingCategory;
use App\Models\Billing\BillingItem;
use App\Models\Billing\BillingTaxCategory;
use App\Models\Ipd\IpdAdmissionType;
use App\Models\Ipd\IpdBedType;
use App\Models\Laboratory\LabTest;
use App\Models\Ot\OtProcedureCategory;
use App\Models\Pharmacy\PharmacyMedication;
use App\Models\Radiology\RadiologyProcedure;
use App\Services\Demo\History\DemoHistoryModule;
use App\Services\Demo\History\HistoryContext;
use Illuminate\Support\Str;

/**
 * The price list for everything the clinical modules charge for. It must exist BEFORE the clinical history runs (Billing only creates a
 * charge when it finds a matching item), so this class runs right after the patients. Prices are invented training prices.
 */
class BillingCatalogHistory implements DemoHistoryModule
{
    public function name(): string
    {
        return 'Price list';
    }

    public function module(): string
    {
        return 'billing';
    }

    public function run(HistoryContext $context): string
    {
        $orgId = $context->organization->id;
        $tax = BillingTaxCategory::firstOrCreate(['organization_id' => $orgId, 'branch_id' => null, 'code' => 'VAT'], ['name' => 'VAT', 'rate' => '0.0000', 'is_inclusive' => false, 'is_active' => true]);
        $created = 0;
        $ensure = function (string $category, string $code, string $name, string $type, string $eventType, string $eventKey, int $price) use ($orgId, $tax, &$created) {
            $cat = BillingCategory::firstOrCreate(['organization_id' => $orgId, 'branch_id' => null, 'code' => $category], ['name' => Str::headline($category), 'is_active' => true]);
            $item = BillingItem::firstOrCreate(
                ['organization_id' => $orgId, 'branch_id' => null, 'item_code' => $code],
                ['category_id' => $cat->id, 'tax_category_id' => $tax->id, 'item_type' => $type, 'name' => $name, 'unit' => 'each', 'base_price' => number_format($price, 2, '.', ''), 'is_taxable' => false,
                    'is_clinically_chargeable' => true, 'clinical_event_type' => $eventType, 'clinical_event_key' => $eventKey, 'is_active' => true],
            );
            // An older item with the same clinical meaning (e.g. the seeded OPD fee) is left alone; only count what this run made.
            $item->wasRecentlyCreated && $created++;
        };

        // OPD: the seeded fee covers a completed encounter. Orders of a whole class (a lab or imaging order) are not charged per order.
        foreach (LabTest::query()->forTenant($orgId)->where('is_active', true)->get() as $test) {
            $ensure('LAB', 'LAB-'.$test->code, $test->name, 'lab_test', 'lab_test', $test->code, 150 + (crc32($test->code) % 12) * 50);
        }
        foreach (RadiologyProcedure::query()->forTenant($orgId)->where('is_active', true)->get() as $procedure) {
            $ensure('RAD', 'RAD-'.$procedure->code, $procedure->name, 'radiology', 'radiology_procedure', $procedure->code, match ($procedure->modality_type) {
                'CT' => 5500, 'MR' => 9000, 'US' => 1200, default => 600
            });
        }
        foreach (PharmacyMedication::query()->where('organization_id', $orgId)->where('is_active', true)->get() as $medication) {
            $ensure('PHARM', 'PH-'.$medication->code, $medication->name, 'medication', 'pharmacy_medication', $medication->code, 2 + (crc32($medication->code) % 9) * 3);
        }

        foreach (IpdAdmissionType::query()->where('organization_id', $orgId)->get() as $type) {
            $ensure('ROOM', 'IPD-ADM-'.$type->code, 'Admission fee — '.$type->name, 'service', 'ipd_admission_fee', $type->code, 800);
        }
        $ensure('ROOM', 'IPD-ADM-GENERAL', 'Admission fee', 'service', 'ipd_admission_fee', 'general', 800);
        foreach (IpdBedType::query()->where('organization_id', $orgId)->get() as $bedType) {
            $ensure('ROOM', 'BED-'.$bedType->code, 'Bed day — '.$bedType->name, 'room', 'ipd_bed_day', $bedType->code, 1200 + (crc32($bedType->code) % 4) * 800);
        }
        $ensure('ROOM', 'BED-GENERAL', 'General bed day', 'room', 'ipd_bed_day', 'general', 1500);

        $ensure('PROC', 'OT-THEATRE', 'Theatre usage', 'procedure', 'ot_theatre_usage', 'general', 6000);
        $ensure('PROC', 'OT-ANESTH', 'Anaesthesia fee', 'procedure', 'ot_anesthesia_fee', 'general', 4000);
        $ensure('PROC', 'OT-SURGEON', 'Surgeon fee', 'procedure', 'ot_surgeon_fee', 'general', 12000);
        $ensure('PROC', 'OT-IMPLANT', 'Implant', 'procedure', 'ot_implant', 'general', 15000);
        $ensure('PROC', 'OT-PROC-GENERAL', 'Procedure fee', 'procedure', 'ot_procedure_fee', 'general', 20000);
        foreach (OtProcedureCategory::query()->where('organization_id', $orgId)->get() as $category) {
            $ensure('PROC', 'OT-PROC-'.$category->code, 'Procedure fee — '.$category->name, 'procedure', 'ot_procedure_fee', $category->code, 15000 + (crc32($category->code) % 5) * 5000);
        }

        $ensure('ROOM', 'ICU-BED', 'ICU bed day', 'room', 'icu_bed_day', 'general', 9000);
        $ensure('ROOM', 'ICU-VENT', 'Ventilator day', 'room', 'icu_ventilator_day', 'general', 6000);
        $ensure('ROOM', 'ICU-CARE', 'Critical care service', 'service', 'icu_critical_care_service', 'general', 3000);
        $ensure('PROC', 'ICU-PROC', 'ICU procedure fee', 'procedure', 'icu_procedure_fee', 'general', 5000);

        foreach ([['ED-VISIT', 'Emergency visit', 800], ['ED-RESUS', 'Resuscitation', 3000], ['ED-PROC', 'Emergency procedure', 600], ['ED-OBS-HR', 'Observation unit (per hour)', 250]] as [$code, $name, $price]) {
            $ensure('CONSULT', $code, $name, 'service', 'ed_event', $code, $price);
        }

        return $created.' price-list items added';
    }
}
