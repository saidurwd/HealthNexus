<?php

namespace Tests\Feature\Analytics;

use App\Models\Appointment;
use App\Models\Billing\BillingInvoice;
use App\Models\Encounter;
use App\Models\Laboratory\LabOrder;
use App\Models\Patient;
use App\Services\Billing\InvoiceLifecycleService;
use Illuminate\Support\Facades\DB;

class FactsTest extends AnalyticsTestCase
{
    public function test_every_loader_runs_on_real_data_without_error(): void
    {
        $patient = $this->patient();
        $this->bill($patient, [['CONSULT', '1000.00'], ['LAB', '500.00']]);
        $this->labReport($patient);
        $r = $this->runLoads();
        foreach ($r as $key => $run) {
            $this->assertSame('ok', $run->status, "$key: {$run->error}");
        }
        $this->assertGreaterThan(15, count($r));
    }

    public function test_revenue_facts_reconcile_to_billing(): void
    {
        $a = $this->bill(null, [['CONSULT', '1000.00'], ['LAB', '500.00']], '5');
        $b = $this->bill(null, [['LAB', '300.00']]);
        $this->pay($a, '600.00');
        $draft = $this->bill(null, [['CONSULT', '77.00']], null, false);
        $cancelled = $this->bill(null, [['CONSULT', '200.00']]);
        app(InvoiceLifecycleService::class)->cancel($cancelled->refresh(), 'Entered twice', $this->superAdmin());
        $this->runLoads();

        $billing = BillingInvoice::query()->whereNotIn('status', ['draft', 'cancelled'])->sum('grand_total');
        $this->assertSame(bcadd((string) $billing, '0', 2), bcadd((string) $this->wh('fact_invoices')->where('is_billed', true)->sum('grand_total'), '0', 2), 'billed invoices add up to Billing');
        $this->assertSame(0, $this->wh('fact_invoices')->where('invoice_id', $draft->id)->count(), 'a draft is not in the warehouse');
        $this->assertSame(0, (int) $this->wh('fact_invoices')->where('invoice_id', $cancelled->id)->value('is_billed'));
        $lines = $this->wh('fact_revenue_lines')->where('is_billed', true);
        $this->assertSame('1800.00', bcadd((string) $lines->clone()->sum('gross'), '0', 2));
        $this->assertSame(bcadd((string) DB::table('billing_invoices')->whereNotIn('status', ['draft', 'cancelled'])->sum('subtotal'), '0', 2), bcadd((string) $lines->clone()->sum('gross'), '0', 2), 'revenue lines add up to the invoices\' subtotals');
        $this->assertSame('1000.00', bcadd((string) $lines->clone()->where('category_code', 'CONSULT')->sum('gross'), '0', 2));
        $this->assertSame('600.00', bcadd((string) $this->wh('fact_payments')->where('status', 'completed')->sum('amount'), '0', 2));
        $this->assertSame('1575.00', bcadd((string) $this->wh('fact_invoices')->where('invoice_id', $a->id)->value('grand_total'), '0', 2));
        $this->assertSame('self', $this->wh('fact_invoices')->where('invoice_id', $a->id)->value('payer_kind'));
    }

    public function test_the_second_run_reads_only_what_changed(): void
    {
        $this->bill(null, [['CONSULT', '100.00']]);
        $first = $this->runLoads(['fact_invoices']);
        $this->assertSame(1, $first['fact_invoices']->rows_written);
        $this->travelTo(now()->addHours(5));
        $second = $this->runLoads(['fact_invoices']);
        $this->assertLessThanOrEqual(1, $second['fact_invoices']->rows_read, 'only the newest row is read again, because of the overlap');
        $this->assertSame(1, $this->wh('fact_invoices')->count(), 'reading it again duplicates nothing');
        $invoice = BillingInvoice::query()->first();
        $this->pay($invoice, '40.00');
        $third = $this->runLoads(['fact_invoices']);
        $this->assertSame(1, $third['fact_invoices']->rows_written);
        $this->assertSame('40.00', bcadd((string) $this->wh('fact_invoices')->value('paid'), '0', 2));
        $this->assertSame(1, $this->wh('fact_invoices')->count(), 'upserted, not duplicated');
    }

    public function test_encounters_and_appointments_carry_a_patient_key_not_a_patient_id(): void
    {
        $patient = $this->patient();
        $doctor = $this->provider();
        Encounter::create(['organization_id' => $this->organization->id, 'branch_id' => $this->branch->id, 'patient_id' => $patient->id, 'encounter_no' => 'E-1', 'encounter_type' => 'opd', 'provider_id' => $doctor->id, 'encounter_date' => '2026-09-08',
            'status' => 'completed', 'source' => 'walk_in', 'started_at' => '2026-09-08 04:00:00', 'completed_at' => '2026-09-08 04:25:00', 'created_by' => $this->superAdmin()->id]);
        $this->runLoads();
        $fact = (array) $this->wh('fact_encounters')->first();
        $this->assertSame(20260908, (int) $fact['date_key']);
        $this->assertSame(25, (int) $fact['duration_minutes']);
        $this->assertSame(10, (int) $fact['hour'], '04:00 UTC is 10:00 in Dhaka');
        $this->assertArrayNotHasKey('patient_id', $fact);
        $this->assertSame((int) $this->wh('dim_patient')->value('patient_key'), (int) $fact['patient_key']);
    }

    public function test_a_fact_keeps_the_patient_description_that_was_true_at_the_time(): void
    {
        $patient = $this->patient(['city' => 'Dhaka']);
        Encounter::create(['organization_id' => $this->organization->id, 'branch_id' => $this->branch->id, 'patient_id' => $patient->id, 'encounter_no' => 'E-2', 'encounter_type' => 'opd', 'provider_id' => $this->provider()->id, 'encounter_date' => '2026-09-09',
            'status' => 'completed', 'source' => 'walk_in', 'created_by' => $this->superAdmin()->id]);
        $this->runLoads();
        $oldKey = (int) $this->wh('fact_encounters')->value('patient_key');
        $this->travelTo(now()->addDays(10));
        Patient::query()->whereKey($patient->id)->update(['city' => 'Sylhet', 'updated_at' => now()]);
        Encounter::create(['organization_id' => $this->organization->id, 'branch_id' => $this->branch->id, 'patient_id' => $patient->id, 'encounter_no' => 'E-3', 'encounter_type' => 'opd', 'provider_id' => $this->provider()->id, 'encounter_date' => now()->toDateString(),
            'status' => 'completed', 'source' => 'walk_in', 'created_by' => $this->superAdmin()->id]);
        $this->runLoads();
        $newKey = (int) $this->wh('fact_encounters')->orderByDesc('encounter_id')->value('patient_key');
        $this->assertNotSame($oldKey, $newKey);
        $this->assertSame('Dhaka', $this->wh('dim_patient')->where('patient_key', $oldKey)->value('district'));
        $this->assertSame('Sylhet', $this->wh('dim_patient')->where('patient_key', $newKey)->value('district'));
        $this->assertSame($oldKey, (int) $this->wh('fact_encounters')->orderBy('encounter_id')->value('patient_key'), 'the old visit still points at the old description');
    }

    public function test_lab_turn_around_and_flags(): void
    {
        $patient = $this->patient();
        $report = $this->labReport($patient, 'Hemoglobin', true);
        $order = LabOrder::query()->find($report->lab_order_id);
        DB::table('lab_orders')->where('id', $order->id)->update(['ordered_at' => '2026-09-10 08:00:00']);
        DB::table('lab_order_items')->where('lab_order_id', $order->id)->update(['collected_at' => '2026-09-10 08:20:00']);
        DB::table('lab_reports')->where('id', $report->id)->update(['generated_at' => '2026-09-10 09:30:00']);
        $this->runLoads();
        $f = (array) $this->wh('fact_lab_orders')->first();
        $this->assertSame(20, (int) $f['tat_collect_min']);
        $this->assertSame(90, (int) $f['tat_report_min']);
        $this->assertSame(1, (int) $f['critical_results']);
        $this->assertSame(1, (int) $f['reported']);
    }

    public function test_appointment_facts(): void
    {
        $patient = $this->patient();
        $doctor = $this->provider();
        Appointment::factory()->create(['organization_id' => $this->organization->id, 'branch_id' => $this->branch->id, 'patient_id' => $patient->id, 'doctor_id' => $this->superAdmin()->id, 'provider_id' => $doctor->id, 'appointment_date' => '2026-09-09', 'status' => 'no_show']);
        $this->runLoads(['dim_patient', 'fact_appointments']);
        $this->assertSame(1, $this->wh('fact_appointments')->where('is_no_show', true)->count());
    }
}
