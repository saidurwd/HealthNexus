<?php

namespace Tests\Feature\Blood;

use App\Models\Blood\BloodDonor;
use App\Models\Blood\BloodStorageLocation;
use App\Models\Blood\BloodUnit;
use App\Models\Clinical\ClinicalAlert;
use App\Models\Laboratory\LabOrder;
use App\Models\Laboratory\LabOrderItem;
use App\Models\Laboratory\LabTest;
use App\Services\Blood\BloodDonationService;
use App\Services\Blood\BloodInventoryService;
use App\Services\Blood\BloodProcessingService;
use App\Services\Blood\BloodTestingService;
use App\Services\Blood\BloodUnitService;
use App\Services\Laboratory\LabReportService;
use App\Services\Laboratory\ResultEntryService;
use App\Services\Laboratory\ResultValidationService;
use App\Services\Laboratory\SpecimenService;
use Illuminate\Validation\ValidationException;

class BloodTestingAndInventoryTest extends BloodTestCase
{
    private function quarantined(?BloodDonor $donor = null)
    {
        $donor ??= $this->donor();
        $donation = app(BloodDonationService::class)->complete(app(BloodDonationService::class)->start($donor, $this->goodScreening($donor), $this->branch, $this->user), 450, $this->user);
        app(BloodProcessingService::class)->separate(BloodUnit::where('donation_id', $donation->id)->firstOrFail(), $this->user);

        return $donation->refresh();
    }

    public function test_clean_results_and_a_group_release_the_units_to_the_right_storage(): void
    {
        $donation = $this->quarantined();
        $this->passTests($donation, 'B', 'negative');
        $released = app(BloodTestingService::class)->release($donation, $this->user);

        $this->assertSame(3, $released);
        $prbc = BloodUnit::where('donation_id', $donation->id)->where('component', 'prbc')->firstOrFail();
        $this->assertSame(BloodUnit::AVAILABLE, $prbc->status);
        $this->assertSame(['B', 'negative'], [$prbc->abo, $prbc->rh]);
        $this->assertSame('fridge', $prbc->location->kind);
        $this->assertSame('freezer', BloodUnit::where('donation_id', $donation->id)->where('component', 'ffp')->firstOrFail()->location->kind);
        $this->assertSame('agitator', BloodUnit::where('donation_id', $donation->id)->where('component', 'platelets')->firstOrFail()->location->kind);
    }

    public function test_a_positive_screen_blocks_every_unit_defers_the_donor_for_good_and_alerts(): void
    {
        $donor = $this->donor();
        $donation = $this->quarantined($donor);
        app(BloodTestingService::class)->applyResult($donation, 'BB-HBSAG', 'Reactive');

        $this->assertSame(3, BloodUnit::where('donation_id', $donation->id)->where('status', BloodUnit::BLOCKED)->where('blocked_reason', 'positive_screen')->count());
        $this->assertSame(BloodDonor::PERMANENTLY_DEFERRED, $donor->refresh()->status);
        $this->assertSame(1, ClinicalAlert::where('category', 'blood_unit_blocked')->count());

        $this->expectException(ValidationException::class);
        app(BloodTestingService::class)->release($donation, $this->user);
    }

    public function test_a_group_that_disagrees_with_the_donors_file_holds_the_units(): void
    {
        $donor = $this->donor();
        $first = $this->releasedDonation('O', 'positive', $donor);
        $this->assertSame('O', $donor->refresh()->abo);
        $donor->update(['last_donation_at' => now()->subYear()]);

        $second = $this->quarantined($donor->refresh());
        $this->passTests($second, 'A', 'positive');

        $this->assertSame(3, BloodUnit::where('donation_id', $second->id)->where('blocked_reason', 'discordant_group')->count());
        $this->assertSame('discordant', $second->tests()->where('test_code', 'BB-ABO')->value('outcome'));
        $this->assertNotNull($first);
    }

    public function test_a_release_needs_every_test_resulted(): void
    {
        $donation = $this->quarantined();
        app(BloodTestingService::class)->applyResult($donation, 'BB-HIV', 'Negative');
        $this->expectException(ValidationException::class);
        app(BloodTestingService::class)->release($donation, $this->user);
    }

    public function test_results_are_read_from_the_laboratory_not_typed_twice(): void
    {
        $donation = $this->quarantined();
        $order = LabOrder::where('clinical_order_id', $donation->clinical_order_id)->firstOrFail();
        $analyst = $this->staff(['super_admin']);
        $reviewer = $this->staff(['super_admin']);
        $specimen = app(SpecimenService::class)->collect($order, ['order_item_ids' => $order->items()->pluck('id')->all()], $analyst);
        app(SpecimenService::class)->receive($specimen, $analyst);
        $values = ['BB-ABO' => 'A', 'BB-RH' => 'positive'];
        foreach (LabOrderItem::where('lab_order_id', $order->id)->get() as $item) {
            $code = LabTest::whereKey($item->test_id)->value('code');
            $result = app(ResultEntryService::class)->enter($item->refresh(), ['qualitative_value' => $values[$code] ?? 'Negative'], $analyst);
            $entered[] = $result;
        }
        foreach ($entered as $result) {
            app(ResultValidationService::class)->technicalValidate($result->refresh(), $reviewer);
        }
        app(LabReportService::class)->finalize($order->refresh(), $reviewer);

        $units = BloodUnit::where('donation_id', $donation->id)->get();
        $this->assertTrue($units->every(fn ($u) => $u->abo === 'A' && $u->rh === 'positive'), 'the lab result reached the units through the event');
        $this->assertNotNull($donation->tests()->where('test_code', 'BB-ABO')->value('lab_result_id'));
        $this->assertSame(7, $donation->tests()->where('outcome', 'pass')->count());
    }

    public function test_a_temperature_excursion_alerts_and_blocks_every_unit_in_that_place_but_a_normal_reading_does_not(): void
    {
        $donation = $this->releasedDonation();
        $fridge = BloodStorageLocation::where('organization_id', $this->organization->id)->where('kind', 'fridge')->orderBy('id')->firstOrFail();
        $inventory = app(BloodInventoryService::class);
        $inventory->recordTemperature($fridge, 4.0, $this->user);
        $this->assertSame(0, BloodUnit::where('status', BloodUnit::BLOCKED)->count());

        $log = $inventory->recordTemperature($fridge, 9.5, $this->user, 'device');
        $this->assertTrue($log->is_excursion);
        $this->assertSame(1, BloodUnit::where('donation_id', $donation->id)->where('component', 'prbc')->where('status', BloodUnit::BLOCKED)->where('blocked_reason', 'temperature_excursion')->count());
        $this->assertSame(1, ClinicalAlert::where('category', 'blood_temperature_excursion')->count());
        $this->assertSame(BloodUnit::AVAILABLE, BloodUnit::where('donation_id', $donation->id)->where('component', 'ffp')->firstOrFail()->status, 'units in another place are not touched');
    }

    public function test_expiry_sweeps_units_and_an_expired_unit_cannot_be_used_under_any_role(): void
    {
        $unit = $this->unit();
        $unit->update(['expires_at' => now()->subMinute()]);

        $this->assertSame(1, app(BloodInventoryService::class)->expireDue());
        $this->assertSame(BloodUnit::EXPIRED, $unit->refresh()->status);

        $this->expectException(ValidationException::class);
        app(BloodUnitService::class)->move($unit, BloodUnit::AVAILABLE, $this->user);
    }

    public function test_fefo_lists_soonest_expiry_first_and_the_summary_counts_by_group(): void
    {
        $old = $this->unit('prbc', 'O', 'positive');
        $new = $this->unit('prbc', 'O', 'positive');
        $old->update(['expires_at' => now()->addDays(3)]);
        $new->update(['expires_at' => now()->addDays(20)]);

        $inventory = app(BloodInventoryService::class);
        $this->assertSame([$old->id, $new->id], $inventory->fefo($this->organization->id, 'prbc', 'O', 'positive')->pluck('id')->all());
        $this->assertSame(2, $inventory->summary($this->organization->id)['O positive']['prbc']);
        $this->assertArrayHasKey('prbc', $inventory->lowStock($this->organization->id));
        $this->assertSame(1, $inventory->warnNearExpiry($this->organization->id));
    }

    public function test_the_unit_trail_is_append_only(): void
    {
        $unit = $this->unit();
        $event = $unit->events()->firstOrFail();
        $this->expectException(\LogicException::class);
        $event->update(['note' => 'edited']);
    }
}
