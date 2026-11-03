<?php

namespace Tests\Feature\Interop;

use App\Interop\Mappers\AllergyIntoleranceMapper;
use App\Interop\Mappers\DiagnosticReportMapper;
use App\Interop\Mappers\InvoiceMapper;
use App\Interop\Mappers\MedicationRequestMapper;
use App\Interop\Mappers\ObservationMapper;
use App\Interop\Mappers\PatientMapper;
use App\Interop\Mappers\ServiceRequestMapper;
use App\Models\Billing\BillingInvoiceItem;
use App\Models\Billing\BillingItem;
use App\Models\Clinical\ClinicalObservation;
use App\Models\Laboratory\LabOrder;
use App\Models\Laboratory\LabResult;
use App\Models\PatientAllergy;
use App\Models\PrescriptionItem;
use Illuminate\Support\Facades\DB;

/**
 * Mappers against golden JSON (guideline override 4). The fixtures are fixed; the only things normalized are generated ids (ULIDs become <id1>, <id2> …). To accept an intended change,
 * run once with GOLDEN=update and READ THE DIFF in git before committing it.
 */
class GoldenFhirTest extends InteropTestCase
{
    private function check(string $name, array $resource): void
    {
        $json = json_encode($resource, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $n = 0;
        $seen = [];
        $json = preg_replace_callback('/\b[0-9A-HJKMNP-TV-Z]{26}\b/', function ($m) use (&$n, &$seen) {
            return $seen[$m[0]] ??= '<id'.(++$n).'>';
        }, $json)."\n";
        $path = base_path("tests/Golden/fhir/$name.json");
        if (getenv('GOLDEN') === 'update' || ! is_file($path)) {
            file_put_contents($path, $json);
        }
        $this->assertSame(file_get_contents($path), $json, "golden file $name.json differs");
    }

    public function test_patient(): void
    {
        $p = $this->patient(['enterprise_patient_no' => 'EPT-000001', 'national_identifier' => '1990123456789', 'first_name' => 'Rina', 'middle_name' => null, 'last_name' => 'Akter', 'sex' => 'F', 'date_of_birth' => '1990-04-12',
            'phone' => '01712345678', 'email' => 'rina@example.org', 'address' => 'House 4, Road 2, Mirpur', 'city' => 'Dhaka']);
        $this->check('patient', app(PatientMapper::class)->toFhir($p->fresh()));
    }

    public function test_allergy(): void
    {
        $p = $this->patient(['enterprise_patient_no' => 'EPT-000001']);
        $a = PatientAllergy::create(['organization_id' => $this->organization->id, 'patient_id' => $p->id, 'substance' => 'Penicillin', 'severity' => 'severe', 'reaction' => 'swelling', 'notes' => 'Lips swelled within minutes', 'is_active' => true]);
        $this->check('allergy', app(AllergyIntoleranceMapper::class)->toFhir($a->fresh()));
    }

    public function test_vital_sign_observation(): void
    {
        $p = $this->patient(['enterprise_patient_no' => 'EPT-000001']);
        $o = ClinicalObservation::create(['organization_id' => $this->organization->id, 'branch_id' => $this->branch->id, 'patient_id' => $p->id, 'context_type' => 'opd', 'code_system' => 'loinc', 'code' => '8867-4', 'display' => 'Heart rate',
            'value_number' => 82, 'unit_code' => '/min', 'effective_at' => now(), 'recorded_at' => now(), 'status' => 'final', 'interpretation' => 'normal', 'reference_low' => 60, 'reference_high' => 100, 'source_module' => 'test']);
        $this->check('observation-vital', app(ObservationMapper::class)->toFhir($o->fresh()));
    }

    public function test_lab_result_observation_service_request_and_report(): void
    {
        $p = $this->patient(['enterprise_patient_no' => 'EPT-000001']);
        $report = $this->labReport($p, 'Hemoglobin');
        $order = LabOrder::query()->first();
        $order->update(['order_number' => 'LAB-0001']);
        $report->update(['report_number' => 'LR-0001']);
        DB::table('lab_tests')->update(['code' => 'HGB']);
        $result = LabResult::query()->first();
        $item = $order->items()->first();

        $this->check('observation-lab', app(ObservationMapper::class)->toFhir($result->fresh()));
        $this->check('service-request-lab', app(ServiceRequestMapper::class)->toFhir($item->fresh()));
        $this->check('diagnostic-report-lab', app(DiagnosticReportMapper::class)->toFhir($report->fresh()));
    }

    public function test_medication_request_and_invoice(): void
    {
        $p = $this->patient(['enterprise_patient_no' => 'EPT-000001']);
        $rx = $this->prescription($p);
        $rx->update(['prescription_no' => 'RX-0001']);
        $this->check('medication-request', app(MedicationRequestMapper::class)->toFhir(PrescriptionItem::query()->first()));

        $inv = $this->invoice($p, '1000.00', '400.00', 'partially_paid');
        $inv->update(['invoice_number' => 'INV-0001']);
        BillingInvoiceItem::create(['invoice_id' => $inv->id, 'billing_item_id' => BillingItem::factory()->create(['organization_id' => $this->organization->id])->id, 'description' => 'Consultation', 'quantity' => 1, 'unit_price' => '1000.00', 'gross_amount' => '1000.00', 'net_amount' => '1000.00']);
        $this->check('invoice', app(InvoiceMapper::class)->toFhir($inv->fresh()));
    }
}
