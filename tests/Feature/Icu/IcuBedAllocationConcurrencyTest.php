<?php

namespace Tests\Feature\Icu;

use App\Services\Icu\IcuBedAllocationService;
use Illuminate\Validation\ValidationException;

/**
 * IcuBedAllocationService delegates directly to IpdBedAllocationService::allocate()/release()
 * (Phase 11 plan decision #1) — it introduces no new locking primitive of its own. The raw
 * two-PDO row-lock proof for ipd_beds already exists and passes in
 * tests\Feature\Ipd\BedAllocationConcurrencyTest; re-asserting the same lock on the same table
 * here would be pure duplicate coverage. This test instead proves the ICU-specific behavior on
 * top of that lock: a second admission can never be assigned the same bed while the first
 * admission's allocation is still active.
 */
class IcuBedAllocationConcurrencyTest extends IcuTestCase
{
    public function test_a_second_admission_cannot_be_assigned_a_bed_already_held_by_another_active_admission(): void
    {
        $bed = $this->makeIcuBed();

        $episodeA = $this->makeEpisode();
        app(IcuBedAllocationService::class)->assign($episodeA->admission, $bed, $this->user);

        $admissionB = $this->makeEpisode()->admission;

        $this->expectException(ValidationException::class);

        app(IcuBedAllocationService::class)->assign($admissionB, $bed, $this->user);
    }

    public function test_it_rejects_assigning_a_non_icu_capable_bed(): void
    {
        $episode = $this->makeEpisode();
        $generalBed = $this->makeBed();

        $this->expectException(ValidationException::class);

        app(IcuBedAllocationService::class)->assign($episode->admission, $generalBed, $this->user);
    }
}
