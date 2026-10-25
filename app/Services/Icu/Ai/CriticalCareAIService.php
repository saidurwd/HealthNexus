<?php

namespace App\Services\Icu\Ai;

use App\Models\Icu\IcuEpisode;
use Illuminate\Support\Carbon;

/**
 * AI-ready extension point only (spec §66-68) — zero concrete implementation, never wired to any
 * automatic action, mirroring OT's "no AI code at all this phase" non-goal from Phase 10. Every
 * implementation must remain advisory and must never autonomously modify the patient's record or
 * treatment; every suggestion must carry an AI-generated/model/confidence/review-status envelope.
 */
interface CriticalCareAIService
{
    public function summarizePatient(IcuEpisode $episode, Carbon $from, Carbon $to): array;

    public function assessDeteriorationRisk(IcuEpisode $episode): array;

    public function draftHandover(IcuEpisode $episode): array;

    public function draftProgressNote(IcuEpisode $episode): array;

    public function forecastBedDemand(int $organizationId, ?int $branchId, Carbon $horizon): array;
}
