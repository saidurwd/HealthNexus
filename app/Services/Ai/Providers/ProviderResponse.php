<?php

namespace App\Services\Ai\Providers;

/** What a provider returns: lines, each with the references of the facts it rests on. */
final class ProviderResponse
{
    /** @param list<array{text:string,refs:list<string>,section?:string,placeholder?:bool}> $lines */
    public function __construct(public readonly array $lines, public readonly string $model = '', public readonly string $version = '', public readonly int $tokensIn = 0, public readonly int $tokensOut = 0) {}
}
