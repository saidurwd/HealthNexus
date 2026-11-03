<?php

namespace App\Contracts\Import;

use App\Services\Import\ImportContext;

/** One kind of bulk import. Every lookup and write must stay inside $context->organizationId. */
interface ImportDefinition
{
    public function key(): string;

    public function title(): string;

    /** Permission needed to run this import, e.g. import.patients. */
    public function permission(): string;

    /** @return list<array{name: string, required: bool, example: string, help?: string}> */
    public function columns(): array;

    /**
     * Clean one raw CSV row and check it.
     *
     * @return array{data: array, errors: list<string>}
     */
    public function validate(array $raw, ImportContext $context): array;

    /** Key that identifies "the same record" inside one file (null = rows are never duplicates of each other). */
    public function fileKey(array $data): ?string;

    /** Description of an existing record this row would duplicate, or null. */
    public function findExisting(array $data, ImportContext $context): ?string;

    /** Create the record. @return array{type: string, id: int} */
    public function commit(array $data, ImportContext $context): array;

    /** Undo one imported record. @return 'removed'|'deactivated'|'kept' */
    public function rollback(string $type, int $id, ImportContext $context): string;
}
