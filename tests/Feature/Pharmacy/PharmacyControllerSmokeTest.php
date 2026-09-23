<?php

namespace Tests\Feature\Pharmacy;

use App\Models\Patient;
use App\Models\Pharmacy\PharmacyQuarantine;
use App\Models\Pharmacy\PharmacySafetyAlert;
use App\Services\Pharmacy\PharmacyDispensingService;
use App\Services\Pharmacy\PharmacyOrderService;
use App\Services\Pharmacy\PharmacyRecallService;
use App\Services\Pharmacy\PharmacyStockCountService;
use App\Services\Pharmacy\PharmacyTransferService;

/**
 * Exercises every GET view end-to-end against real seeded rows — the same technique that
 * caught real bugs in Laboratory's and Radiology's controller smoke tests.
 */
class PharmacyControllerSmokeTest extends PharmacyTestCase
{
    private \App\Models\Pharmacy\PharmacyMedication $medication;

    private \App\Models\Pharmacy\PharmacyStore $store;

    private \App\Models\Pharmacy\PharmacyStore $destinationStore;

    private \App\Models\Pharmacy\PharmacyOrder $order;

    private \App\Models\Pharmacy\PharmacyDispensing $dispensing;

    private \App\Models\Pharmacy\PharmacyTransfer $transfer;

    private \App\Models\Pharmacy\PharmacyStockCount $stockCount;

    private \App\Models\Pharmacy\PharmacyRecall $recall;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->medication = $this->makeMedication();
        $this->store = $this->makeStore('PH-MAIN');
        $this->destinationStore = $this->makeStore('PH-WARD');
        $this->receiveBatch($this->store, $this->medication, 100);

        $this->patient = Patient::factory()->create(['company_id' => $this->company->id]);
        $prescription = $this->makePrescription($this->patient, [
            ['medicine_name' => $this->medication->name, 'quantity' => 10],
        ]);
        $this->order = app(PharmacyOrderService::class)->registerFromPrescription($prescription);

        $orderItem = $this->order->items->first();
        $this->dispensing = app(PharmacyDispensingService::class)->dispense($this->order, $this->store, [
            ['order_item_id' => $orderItem->id, 'quantity' => 5],
        ], $this->user);

        $batch = $this->medication->batches()->first();

        $this->transfer = app(PharmacyTransferService::class)->request($this->store, $this->destinationStore, [
            ['medication_id' => $this->medication->id, 'batch_id' => $batch->id, 'quantity_requested' => 5],
        ], $this->user);

        $this->stockCount = app(PharmacyStockCountService::class)->start($this->store, $this->user);

        $this->recall = app(PharmacyRecallService::class)->initiate($batch, 'Manufacturer notice.', $this->user);

        PharmacySafetyAlert::create([
            'company_id' => $this->company->id, 'branch_id' => null,
            'order_item_id' => $orderItem->id, 'medication_id' => $this->medication->id,
            'alert_type' => PharmacySafetyAlert::TYPE_ALLERGY, 'severity' => PharmacySafetyAlert::SEVERITY_SEVERE,
            'explanation' => 'Test alert.', 'is_overridden' => false,
        ]);
    }

    public function test_dashboard_loads(): void
    {
        $this->get('/admin/pharmacy')->assertOk();
    }

    public function test_catalog_pages_load(): void
    {
        $this->get('/admin/pharmacy/medications')->assertOk();
        $this->get('/admin/pharmacy/medications/create')->assertOk();
        $this->get('/admin/pharmacy/medications/'.$this->medication->id.'/edit')->assertOk();
        $this->get('/admin/pharmacy/generics')->assertOk();
        $this->get('/admin/pharmacy/generics/create')->assertOk();
        $this->get('/admin/pharmacy/brands')->assertOk();
        $this->get('/admin/pharmacy/brands/create')->assertOk();
    }

    public function test_order_pages_load(): void
    {
        $this->get('/admin/pharmacy/orders')->assertOk();
        $this->get('/admin/pharmacy/orders/'.$this->order->id)->assertOk();
    }

    public function test_dispensing_pages_load(): void
    {
        $this->get('/admin/pharmacy/dispensing')->assertOk();
        $this->get('/admin/pharmacy/dispensing/'.$this->dispensing->id)->assertOk();
        $this->get('/admin/pharmacy/orders/'.$this->order->id.'/dispense')->assertOk();
    }

    public function test_stock_pages_load(): void
    {
        $this->get('/admin/pharmacy/stock?store_id='.$this->store->id)->assertOk();
        $this->get('/admin/pharmacy/batches')->assertOk();
        $this->get('/admin/pharmacy/stock/receive')->assertOk();
    }

    public function test_transfer_pages_load(): void
    {
        $this->get('/admin/pharmacy/transfers')->assertOk();
        $this->get('/admin/pharmacy/transfers/create')->assertOk();
        $this->get('/admin/pharmacy/transfers/'.$this->transfer->id)->assertOk();
    }

    public function test_stock_count_pages_load(): void
    {
        $this->get('/admin/pharmacy/stock-counts')->assertOk();
        $this->get('/admin/pharmacy/stock-counts/'.$this->stockCount->id)->assertOk();
    }

    public function test_quarantine_page_loads(): void
    {
        $this->assertGreaterThan(0, PharmacyQuarantine::count());
        $this->get('/admin/pharmacy/quarantine')->assertOk();
    }

    public function test_recall_pages_load(): void
    {
        $this->get('/admin/pharmacy/recalls')->assertOk();
        $this->get('/admin/pharmacy/recalls/'.$this->recall->id)->assertOk();
    }

    public function test_safety_alerts_page_loads(): void
    {
        $this->get('/admin/pharmacy/safety-alerts')->assertOk();
    }

    public function test_controlled_drugs_page_loads(): void
    {
        $this->get('/admin/pharmacy/controlled-drugs')->assertOk();
    }

    public function test_settings_page_loads(): void
    {
        $this->get('/admin/pharmacy/settings')->assertOk();
    }

    public function test_patient_history_page_loads(): void
    {
        $this->get('/admin/patients/'.$this->patient->id.'/pharmacy-history')->assertOk();
    }
}
