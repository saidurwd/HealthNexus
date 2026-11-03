<?php

namespace Tests\Feature\Icu;

use App\Models\Ipd\IpdBed;
use App\Services\Icu\IcuBedAllocationService;
use App\Services\Icu\IcuEpisodeService;
use App\Services\Icu\IcuInfusionService;
use App\Services\Icu\IcuProgressNoteService;
use Illuminate\Validation\ValidationException;

class NegativeIcuTest extends IcuTestCase
{
    public function test_occupied_bed_cannot_be_assigned(): void
    {
        $bed = $this->makeIcuBed();
        $episodeA = $this->makeEpisode();
        app(IcuBedAllocationService::class)->assign($episodeA->admission, $bed, $this->user);

        $episodeB = $this->makeEpisode();

        $this->expectException(ValidationException::class);

        app(IcuBedAllocationService::class)->assign($episodeB->admission, $bed, $this->user);
    }

    public function test_inactive_bed_cannot_be_assigned(): void
    {
        $bed = $this->makeIcuBed();
        $bed->update(['status' => IpdBed::STATUS_MAINTENANCE]);

        $request = $this->makeAdmissionRequest();

        $this->expectException(ValidationException::class);

        app(IcuEpisodeService::class)->admit($request->fresh(), ['bed_id' => $bed->id], $this->user);
    }

    public function test_duplicate_admission_from_the_same_request_is_rejected(): void
    {
        $episode = $this->makeEpisode();
        $request = $episode->admissionRequest->fresh();
        $anotherBed = $this->makeIcuBed();

        $this->expectException(ValidationException::class);

        app(IcuEpisodeService::class)->admit($request, ['bed_id' => $anotherBed->id], $this->user);
    }

    public function test_finalized_progress_note_cannot_be_edited(): void
    {
        $episode = $this->makeEpisode();
        $note = app(IcuProgressNoteService::class)->create($episode, ['subjective' => 'x'], $this->user);
        app(IcuProgressNoteService::class)->finalize($note, $this->user);

        $this->expectException(ValidationException::class);

        app(IcuProgressNoteService::class)->update($note->fresh(), ['subjective' => 'edited']);
    }

    public function test_titrating_a_stopped_infusion_is_rejected(): void
    {
        $episode = $this->makeEpisode();
        $infusion = app(IcuInfusionService::class)->start($episode, ['medication' => 'Norepinephrine', 'initial_rate' => 5], $this->user);
        app(IcuInfusionService::class)->stop($infusion, $this->user);

        $this->expectException(ValidationException::class);

        app(IcuInfusionService::class)->titrate($infusion->fresh(), 10, $this->user);
    }
}
