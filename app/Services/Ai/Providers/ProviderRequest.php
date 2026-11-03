<?php

namespace App\Services\Ai\Providers;

/**
 * What a provider is given: the task, the instruction, and facts. A fact is {ref, text, section, kind, untrusted}. The provider phrases facts; it has no tools and nothing to call.
 */
final class ProviderRequest
{
    /** @param list<array{ref:string,text:string,section?:string,kind?:string,untrusted?:bool,placeholder?:bool}> $items */
    public function __construct(public readonly string $task, public readonly string $instruction, public readonly array $items, public readonly string $language = 'en', public readonly string $system = '') {}
}
