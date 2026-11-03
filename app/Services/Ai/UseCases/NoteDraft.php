<?php

namespace App\Services\Ai\UseCases;

use App\Models\Ai\AiOutput;
use App\Models\Encounter;
use App\Models\User;
use App\Services\Ai\AiCall;
use App\Services\Ai\AiGateway;
use App\Services\Ai\ContextEngine;

/** Use case 2: the facts of a visit laid out as a draft note. The assessment and plan are left for the clinician. */
class NoteDraft
{
    public function __construct(private readonly ContextEngine $context, private readonly AiGateway $gateway) {}

    public function draft(User $user, Encounter $encounter): AiOutput
    {
        $items = $this->context->visit($user, $encounter);

        return $this->gateway->run(new AiCall((int) $encounter->organization_id, $user, 'note_draft', 'draft_note', $items, 'note_draft', __('Draft note for visit :n', ['n' => $encounter->encounter_no]), (int) $encounter->patient_id,
            app()->getLocale() === 'bn' ? 'bn' : 'en', __('A draft of the facts only. It has no assessment or plan: those are yours to write.')));
    }
}
