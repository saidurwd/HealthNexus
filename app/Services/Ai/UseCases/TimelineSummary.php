<?php

namespace App\Services\Ai\UseCases;

use App\Models\Ai\AiOutput;
use App\Models\Patient;
use App\Models\User;
use App\Services\Ai\AiCall;
use App\Services\Ai\AiGateway;
use App\Services\Ai\ContextEngine;

/** Use case 1: a patient's record read back as cited facts. */
class TimelineSummary
{
    public function __construct(private readonly ContextEngine $context, private readonly AiGateway $gateway) {}

    public function summarise(User $user, Patient $patient, ?int $days = null): AiOutput
    {
        $items = $this->context->timeline($user, $patient, $days);

        return $this->gateway->run(new AiCall((int) $patient->organization_id, $user, 'timeline_summary', 'summarise_timeline', $items, 'summary', __('Summary of the record'), (int) $patient->id, app()->getLocale() === 'bn' ? 'bn' : 'en',
            __('Built from :n facts in the record. Anything not recorded here is not in this summary.', ['n' => count($items)])));
    }
}
