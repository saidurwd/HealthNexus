<?php

namespace App\Services\Import\Definitions;

use App\Contracts\Import\ImportDefinition;
use App\Services\Import\ImportContext;
use App\Services\Search\Normalizer;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

abstract class BaseImport implements ImportDefinition
{
    public function fileKey(array $data): ?string
    {
        return null;
    }

    public function findExisting(array $data, ImportContext $context): ?string
    {
        return null;
    }

    protected function text(array $raw, string $name): ?string
    {
        $value = trim((string) ($raw[$name] ?? ''));

        return $value === '' ? null : preg_replace('/\s+/u', ' ', $value);
    }

    protected function number(array $raw, string $name, array &$errors, bool $required = false, ?float $min = null): ?string
    {
        $value = $this->text($raw, $name);
        if ($value === null) {
            $required && $errors[] = "$name is required.";

            return null;
        }

        $value = str_replace(',', '', Normalizer::latinDigits($value));
        if (! is_numeric($value) || ($min !== null && (float) $value < $min)) {
            $errors[] = "$name must be a number".($min !== null ? " of at least $min" : '').'. Example: 250 or 250.50.';

            return null;
        }

        return $value;
    }

    protected function boolean(array $raw, string $name, bool $default = false): bool
    {
        $value = strtolower((string) ($raw[$name] ?? ''));

        return $value === '' ? $default : in_array($value, ['1', 'yes', 'y', 'true', 'হ্যাঁ'], true);
    }

    protected function date(array $raw, string $name, array &$errors, bool $required = false): ?string
    {
        $value = $this->text($raw, $name);
        if ($value === null) {
            $required && $errors[] = "$name is required.";

            return null;
        }

        $value = Normalizer::latinDigits($value);
        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'd.m.Y'] as $format) {
            try {
                $date = Carbon::createFromFormat('!'.$format, $value);
            } catch (\Throwable) {
                continue;
            }

            if ($date !== false && $date->format($format) === $value) {
                return $date->toDateString();
            }
        }

        $errors[] = "$name must be a date like 2026-10-25 or 25/10/2026.";

        return null;
    }

    protected function required(array $raw, string $name, array &$errors, int $max = 255): ?string
    {
        $value = $this->text($raw, $name);
        if ($value === null) {
            $errors[] = "$name is required.";

            return null;
        }
        if (mb_strlen($value) > $max) {
            $errors[] = "$name is longer than $max characters.";
        }

        return $value;
    }

    protected function oneOf(array $raw, string $name, array $allowed, array &$errors, ?string $default = null): ?string
    {
        $value = $this->text($raw, $name);
        if ($value === null) {
            return $default;
        }
        $lower = strtolower($value);
        if (! in_array($lower, $allowed, true)) {
            $errors[] = "$name must be one of: ".implode(', ', $allowed).'.';

            return null;
        }

        return $lower;
    }

    /**
     * Take a record back: delete it when nothing uses it, deactivate it when something does, keep it when it cannot be deactivated.
     *
     * @param  class-string<Model>  $model
     * @param  callable(Model): bool  $inUse
     */
    protected function removeOrDeactivate(string $model, int $id, int $organizationId, callable $inUse): string
    {
        $record = $model::query()->where('organization_id', $organizationId)->find($id);
        if (! $record) {
            return 'removed';
        }

        if (! $inUse($record)) {
            $record->delete();

            return 'removed';
        }

        if (array_key_exists('is_active', $record->getAttributes())) {
            $record->forceFill(['is_active' => false])->save();

            return 'deactivated';
        }

        return 'kept';
    }
}
