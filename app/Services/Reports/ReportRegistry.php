<?php

namespace App\Services\Reports;

use App\Contracts\Reports\ReportDefinition;
use Illuminate\Validation\ValidationException;

class ReportRegistry
{
    public function find(string $key): ReportDefinition
    {
        foreach (config('reports.definitions') as $class) {
            $definition = app($class);

            if ($definition->key() === $key) {
                return $definition;
            }
        }

        throw ValidationException::withMessages(['report' => "Unknown report [$key]."]);
    }

    /** @return list<ReportDefinition> */
    public function all(): array
    {
        return array_map(fn ($class) => app($class), config('reports.definitions'));
    }
}
