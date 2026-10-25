<?php

namespace App\Services\Import;

use App\Contracts\Import\ImportDefinition;
use Illuminate\Validation\ValidationException;

class ImportRegistry
{
    public function find(string $key): ImportDefinition
    {
        foreach ($this->all() as $definition) {
            if ($definition->key() === $key) {
                return $definition;
            }
        }

        throw ValidationException::withMessages(['import' => "Unknown import [$key]."]);
    }

    /** @return list<ImportDefinition> */
    public function all(): array
    {
        return array_map(fn ($class) => app($class), config('imports.definitions'));
    }
}
