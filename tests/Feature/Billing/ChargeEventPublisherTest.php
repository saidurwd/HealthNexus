<?php

namespace Tests\Feature\Billing;

use App\Contracts\Billing\ChargeEventPublisher;
use App\Events\Billing\ChargeReversalRequested;
use App\Events\Clinical\EncounterCompleted;
use App\Models\Billing\BillingCategory;
use App\Models\Billing\BillingCharge;
use App\Models\Billing\BillingItem;
use App\Models\Billing\ChargeEventLog;
use App\Models\Encounter;
use App\Models\Patient;
use App\Services\Billing\Events\ChargeEventData;
use App\Services\Billing\Events\ChargeReconciliationReport;

/** H-05: one contract, one key format, idempotent hand-off, observable and reconcilable. */
class ChargeEventPublisherTest extends BillingTestCase
{
    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->patient = Patient::factory()->create(['organization_id' => $this->organization->id]);
        $this->makeItem('ED-OBS', null);
    }

    private function makeItem(string $code, ?string $eventType = null, ?string $eventKey = null): BillingItem
    {
        $category = BillingCategory::firstOrCreate(
            ['organization_id' => $this->organization->id, 'code' => 'GEN'],
            ['branch_id' => null, 'name' => 'General', 'is_active' => true],
        );

        return BillingItem::create([
            'organization_id' => $this->organization->id, 'branch_id' => null, 'category_id' => $category->id,
            'item_code' => $code, 'item_type' => 'service', 'name' => $code, 'base_price' => '1200.00', 'is_taxable' => false,
            'is_clinically_chargeable' => $eventType !== null, 'clinical_event_type' => $eventType, 'clinical_event_key' => $eventKey,
            'is_active' => true,
        ]);
    }

    private function event(array $o = []): ChargeEventData
    {
        return new ChargeEventData(...array_merge([
            'organizationId' => $this->organization->id, 'branchId' => $this->branch->id, 'patientId' => $this->patient->id,
            'module' => 'ED', 'serviceCode' => 'ED-OBS', 'sourceEventId' => 501, 'visitRef' => 77,
        ], $o));
    }

    public function test_key_follows_the_contract_format(): void
    {
        $this->assertSame('ED:77:ED-OBS:501', $this->event()->idempotencyKey);
    }

    public function test_publishing_creates_a_priced_charge_and_a_log_row(): void
    {
        $log = app(ChargeEventPublisher::class)->publish($this->event(['sourceType' => 'ed_visit', 'sourceId' => 9]));

        $this->assertSame('accepted', $log->outcome);
        $charge = BillingCharge::findOrFail($log->billing_charge_id);
        $this->assertSame('1200.00', $charge->unit_price);
        $this->assertSame('ED:77:ED-OBS:501', $charge->idempotency_key);
        $this->assertSame('ed_visit', $charge->source_type);
    }

    public function test_duplicate_publish_creates_one_charge_and_counts_the_repeat(): void
    {
        $publisher = app(ChargeEventPublisher::class);

        $publisher->publish($this->event());
        $again = $publisher->publish($this->event());

        $this->assertSame(1, BillingCharge::count());
        $this->assertSame(1, ChargeEventLog::count());
        $this->assertSame(2, $again->publish_count);
    }

    public function test_unknown_service_code_is_logged_as_rejected_and_retried_after_the_catalogue_is_fixed(): void
    {
        $publisher = app(ChargeEventPublisher::class);

        $first = $publisher->publish($this->event(['serviceCode' => 'ED-NEW']));
        $this->assertSame('rejected', $first->outcome);
        $this->assertSame(0, BillingCharge::count());

        $this->makeItem('ED-NEW');
        $retry = $publisher->publish($this->event(['serviceCode' => 'ED-NEW']));

        $this->assertSame('accepted', $retry->outcome);
        $this->assertSame(1, ChargeEventLog::count());
    }

    public function test_no_charge_events_are_recorded_but_never_billed(): void
    {
        $log = app(ChargeEventPublisher::class)->publish($this->event(['isChargeable' => false, 'noChargeReason' => 'Staff waiver']));

        $this->assertSame('not_chargeable', $log->outcome);
        $this->assertSame(0, BillingCharge::count());
    }

    public function test_reversal_cancels_an_unbilled_charge_and_keeps_the_original_row(): void
    {
        app(ChargeEventPublisher::class)->publish($this->event());

        event(new ChargeReversalRequested($this->organization->id, 'ED:77:ED-OBS:501', 'Entered on wrong patient', $this->user->id));

        $log = ChargeEventLog::firstOrFail();
        $this->assertSame('reversed', $log->outcome);
        $this->assertSame('cancelled', BillingCharge::findOrFail($log->billing_charge_id)->status);
    }

    public function test_reversing_an_invoiced_charge_is_flagged_for_a_credit_note(): void
    {
        $log = app(ChargeEventPublisher::class)->publish($this->event());
        BillingCharge::whereKey($log->billing_charge_id)->update(['status' => 'billed', 'billed_at' => now()]);

        $result = app(ChargeEventPublisher::class)->reverse($this->organization->id, $log->idempotency_key, 'Service not delivered');

        $this->assertSame('reversal_needs_credit_note', $result->outcome);
        $this->assertSame('billed', BillingCharge::findOrFail($log->billing_charge_id)->status);
    }

    public function test_charges_made_by_existing_modules_appear_in_the_log_and_the_reconciliation(): void
    {
        $this->makeItem('OPD-CONSULT', 'encounter', 'completed');
        $encounter = Encounter::factory()->create(['organization_id' => $this->organization->id, 'branch_id' => $this->branch->id, 'patient_id' => $this->patient->id]);

        event(new EncounterCompleted($encounter));
        event(new EncounterCompleted($encounter));

        $log = ChargeEventLog::where('module', 'OPD')->firstOrFail();
        $this->assertSame("OPD:{$encounter->id}:OPD-CONSULT:{$encounter->id}", $log->idempotency_key);
        $this->assertSame(1, BillingCharge::count());

        app(ChargeEventPublisher::class)->publish($this->event(['serviceCode' => 'NOPE']));
        $report = app(ChargeReconciliationReport::class)->run($this->organization->id, now()->subDay(), now()->addDay());

        $this->assertCount(1, $report['events_without_charge']);
        $this->assertCount(0, $report['charges_without_event']);
    }
}
