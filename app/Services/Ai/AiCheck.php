<?php

namespace App\Services\Ai;

use App\Models\Organization;
use App\Services\ModuleManager;

/** The daily round: lapsed drafts expire, drift is looked for. One hospital's failure does not stop the rest. */
class AiCheck
{
    public function __construct(private readonly OutputService $outputs, private readonly Monitoring $monitoring, private readonly ModuleManager $modules) {}

    /** @return array{expired:int,drift:list<string>} */
    public function run(int $organizationId): array
    {
        return ['expired' => $this->outputs->expireDue($organizationId), 'drift' => $this->monitoring->drift($organizationId)];
    }

    public function runAll(): int
    {
        $n = 0;
        foreach (Organization::query()->where('is_active', true)->pluck('id') as $id) {
            if (! $this->modules->isEnabled('ai', (int) $id)) {
                continue;
            }
            try {
                $this->run((int) $id);
                $n++;
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return $n;
    }
}
