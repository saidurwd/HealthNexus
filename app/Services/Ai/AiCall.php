<?php

namespace App\Services\Ai;

use App\Models\User;

/** One request to the gateway. Facts are {ref, text, section, kind, untrusted?, placeholder?}; nothing else reaches a provider. */
final class AiCall
{
    /** @param list<array<string,mixed>> $items */
    public function __construct(
        public readonly int $organizationId, public readonly User $user, public readonly string $useCase, public readonly string $task, public readonly array $items,
        public readonly string $kind, public readonly string $title, public readonly ?int $patientId = null, public readonly string $language = 'en', public readonly ?string $uncertainty = null,
        /** @var list<string> text from outside (a question) that is checked for instructions but never passed on as a fact */
        public readonly array $inputs = [],
    ) {}
}
