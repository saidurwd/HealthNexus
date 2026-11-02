<?php

namespace App\Services\Ai\Providers;

use App\Models\Ai\AiModel;
use App\Models\Ai\AiProvider;
use RuntimeException;

class ProviderFactory
{
    public function for(AiProvider $provider, AiModel $model): AiProviderContract
    {
        return match ($provider->kind) {
            'fake' => app(FakeProvider::class),
            'rules' => app(RulesProvider::class),
            'http' => new HttpProvider($provider, $model->name),
            default => throw new RuntimeException('Unknown provider kind.'),
        };
    }
}
