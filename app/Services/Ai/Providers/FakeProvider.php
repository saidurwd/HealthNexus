<?php

namespace App\Services\Ai\Providers;

use Closure;

/** For tests and red-team runs: behaves like the rules engine unless a script says otherwise (so a misbehaving model can be simulated). */
class FakeProvider implements AiProviderContract
{
    private static ?Closure $script = null;

    public static function script(?Closure $script): void
    {
        self::$script = $script;
    }

    public function complete(ProviderRequest $request): ProviderResponse
    {
        if (self::$script !== null) {
            return new ProviderResponse((self::$script)($request), 'fake', '1');
        }

        return (new RulesProvider)->complete($request);
    }
}
