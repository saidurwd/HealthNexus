<?php

namespace App\Services\Ai\Providers;

interface AiProviderContract
{
    public function complete(ProviderRequest $request): ProviderResponse;
}
