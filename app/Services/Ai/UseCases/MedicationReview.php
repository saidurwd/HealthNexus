<?php

namespace App\Services\Ai\UseCases;

use App\Models\Ai\AiOutput;
use App\Models\Patient;
use App\Models\User;
use App\Services\Ai\AiCall;
use App\Services\Ai\AiGateway;
use App\Services\Ai\ContextEngine;

/**
 * Use case: a pharmacist's review sheet. Allergies, the medicines now active, the pharmacy's own safety alerts and plain counts are laid side by side with their sources. It never says what to start, stop or change, and it
 * does not find interactions itself: the Pharmacy module's alerts are restated as they were raised.
 */
class MedicationReview
{
    public function __construct(private readonly ContextEngine $context, private readonly AiGateway $gateway) {}

    public function review(User $user, Patient $patient): AiOutput
    {
        $items = $this->context->medications($user, $patient);

        return $this->gateway->run(new AiCall((int) $patient->organization_id, $user, 'medication_review', 'medication_review', $items, 'medicines', __('Medication facts for review'), (int) $patient->id, app()->getLocale() === 'bn' ? 'bn' : 'en',
            __('Built from the prescriptions, allergies and pharmacy alerts recorded in the chart. A medicine bought outside or never recorded is not here, and a missing alert does not mean there is no interaction.')));
    }
}
