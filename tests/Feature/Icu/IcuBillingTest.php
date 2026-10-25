<?php

namespace Tests\Feature\Icu;

use App\Models\Icu\IcuChargeEvent;
use App\Services\Icu\IcuChargeService;

class IcuBillingTest extends IcuTestCase
{
    public function test_bed_day_charge_is_idempotent_for_the_same_calendar_day(): void
    {
        $episode = $this->makeEpisode();
        $today = now()->toDateString();

        $first = app(IcuChargeService::class)->chargeBedDay($episode, $today, $this->user);
        $second = app(IcuChargeService::class)->chargeBedDay($episode, $today, $this->user);

        $this->assertSame(1, IcuChargeEvent::where('episode_id', $episode->id)->where('charge_type', 'bed_day')->where('charge_date', $today)->count());

        if ($first && $second) {
            $this->assertSame($first->id, $second->id);
        }
    }

    public function test_bed_day_and_ventilator_day_charges_are_distinct_events(): void
    {
        $episode = $this->makeEpisode();
        $today = now()->toDateString();

        app(IcuChargeService::class)->chargeBedDay($episode, $today, $this->user);
        app(IcuChargeService::class)->chargeVentilatorDay($episode, $today, $this->user);

        $this->assertSame(2, IcuChargeEvent::where('episode_id', $episode->id)->where('charge_date', $today)->count());
    }
}
