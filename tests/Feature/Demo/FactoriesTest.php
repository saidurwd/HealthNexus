<?php

namespace Tests\Feature\Demo;

use App\Models\Billing\BillingItem;
use App\Models\Ed\EdBay;
use App\Models\Ed\EdVisit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FactoriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_billing_and_ed_factories_build_consistent_records(): void
    {
        $item = BillingItem::factory()->forEvent('lab_test', 'HGB')->create();
        $this->assertSame($item->organization_id, $item->category->organization_id);
        $this->assertTrue($item->is_clinically_chargeable);

        $bay = EdBay::factory()->create();
        $this->assertSame($bay->zone->organization_id, $bay->organization_id);

        $visit = EdVisit::factory()->triaged(2)->create();
        $this->assertSame($visit->organization_id, $visit->patient->organization_id);
        $this->assertSame($visit->encounter->patient_id, $visit->patient_id);
        $this->assertSame(2, $visit->triage_level);
    }
}
