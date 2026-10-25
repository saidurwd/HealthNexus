<?php

namespace Tests\Feature\Icu;

use App\Models\Icu\IcuEpisode;
use App\Services\Icu\IcuAssessmentService;
use App\Services\Icu\IcuDischargeService;
use App\Services\Icu\IcuEpisodeLifecycleService;
use App\Services\Icu\IcuObservationService;
use App\Services\Icu\IcuProgressNoteService;
use App\Services\Icu\IcuTransferService;
use Illuminate\Validation\ValidationException;

class IcuEpisodeLifecycleTest extends IcuTestCase
{
    public function test_full_admit_assess_observe_transfer_sequence(): void
    {
        $episode = $this->makeEpisode();

        $this->assertSame(IcuEpisode::STATUS_ADMITTED, $episode->status);
        $this->assertNotNull($episode->admission_id);
        $this->assertNotNull($episode->icu_bed_id);

        $assessment = app(IcuAssessmentService::class)->create($episode, ['sections' => ['general' => 'Alert and oriented']], $this->user);
        $this->assertSame('draft', $assessment->status);

        $observation = app(IcuObservationService::class)->record($episode, ['observation_type' => 'heart_rate', 'value_numeric' => 88], $this->user);
        $this->assertSame(88.0, $observation->value_numeric);

        $note = app(IcuProgressNoteService::class)->create($episode, ['subjective' => 'Stable overnight'], $this->user);
        app(IcuProgressNoteService::class)->finalize($note, $this->user);
        $this->assertSame('final', $note->fresh()->status);

        $episode = app(IcuTransferService::class)->completeTransfer($episode->fresh(), 'ward', $this->user, 'Improved, ready for ward');

        $this->assertSame(IcuEpisode::STATUS_STEP_DOWN, $episode->status);
    }

    public function test_same_status_transition_is_an_idempotent_no_op(): void
    {
        $episode = $this->makeEpisode();

        $result = app(IcuEpisodeLifecycleService::class)->transitionTo($episode, IcuEpisode::STATUS_ADMITTED);

        $this->assertSame(IcuEpisode::STATUS_ADMITTED, $result->status);
    }

    public function test_invalid_transition_is_rejected(): void
    {
        $episode = $this->makeEpisode();
        app(IcuEpisodeLifecycleService::class)->transitionTo($episode, IcuEpisode::STATUS_TRANSFERRED);

        $this->expectException(ValidationException::class);

        // Transferred is terminal — moving back to admitted must be rejected.
        app(IcuEpisodeLifecycleService::class)->transitionTo($episode->fresh(), IcuEpisode::STATUS_ADMITTED);
    }

    public function test_finalized_assessment_cannot_be_edited_directly(): void
    {
        $episode = $this->makeEpisode();
        $assessment = app(IcuAssessmentService::class)->create($episode, ['sections' => []], $this->user);
        app(IcuAssessmentService::class)->finalize($assessment, $this->user);

        $this->expectException(ValidationException::class);

        app(IcuAssessmentService::class)->update($assessment->fresh(), ['sections' => ['general' => 'changed']]);
    }

    public function test_finalized_discharge_summary_locks_the_episode(): void
    {
        $episode = $this->makeEpisode();
        $summary = app(IcuDischargeService::class)->start($episode, ['destination' => 'discharge', 'current_status' => 'Stable'], $this->user);

        app(IcuDischargeService::class)->finalize($summary, $this->user);

        $this->assertSame(IcuEpisode::STATUS_DISCHARGED, $episode->fresh()->status);
        $this->assertNotNull($episode->fresh()->discharged_at);

        $this->expectException(ValidationException::class);

        app(IcuDischargeService::class)->finalize($summary->fresh(), $this->user);
    }
}
