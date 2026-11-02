<?php

namespace App\Services\Ai\Providers;

/**
 * The local engine: no model, no network. It only re-states the facts it is given, in order, grouped by section, each line keeping its reference. It cannot add a claim, so nothing it says is unsupported.
 */
class RulesProvider implements AiProviderContract
{
    public function complete(ProviderRequest $request): ProviderResponse
    {
        $sections = [];
        foreach ($request->items as $item) {
            $sections[$item['section'] ?? 'Facts'][] = $item;
        }
        $lines = [];
        foreach ($sections as $section => $items) {
            foreach ($items as $item) {
                $lines[] = ['text' => (string) $item['text'], 'refs' => ! empty($item['placeholder']) ? [] : [(string) $item['ref']], 'section' => $section, 'placeholder' => ! empty($item['placeholder'])];
            }
        }

        return new ProviderResponse($lines, 'rules-extractive', '1');
    }
}
