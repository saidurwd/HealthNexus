<?php

namespace App\Services\Portal;

use App\Contracts\Integration\AiProviderGateway;

/**
 * The place a patient-facing assistant will plug in (Phase 20). Today it answers nothing: the Null adapter says "not enabled" and no screen calls this. When Phase 20 arrives it must
 * (a) never give clinical advice, (b) hand over to a person on any urgent word, (c) log every answer. See D-P17.
 */
class PortalAssistant
{
    public function __construct(private readonly AiProviderGateway $ai, private readonly MessagingService $messages) {}

    public function suggestReply(string $question): ?string
    {
        if ($this->messages->looksUrgent($question)) {
            return null; // urgent text always goes to a person
        }
        $result = $this->ai->suggest('portal.faq', ['question' => $question]);

        return $result->handled ? (string) ($result->data['answer'] ?? '') ?: null : null;
    }
}
