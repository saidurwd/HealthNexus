<?php

namespace App\Contracts\Reports;

use App\Services\Reports\ReportContext;
use Illuminate\Support\LazyCollection;

/**
 * A declarative report (guideline 14.4): who may run it, which filters it accepts, which columns it has and how to read the rows.
 * `rows()` MUST filter by $context->organizationId (and branch where the data is branch-level); the framework re-checks nothing
 * for you because the query is the definition's own.
 */
interface ReportDefinition
{
    public function key(): string;

    public function title(): string;

    /** Permission needed to request this export, e.g. patients.export. */
    public function permission(): string;

    /** Laravel validation rules for the filters the report accepts. */
    public function filterRules(): array;

    /** @return list<array{key: string, label: string, sensitive?: bool}> sensitive columns are masked unless the user holds sensitivePermission() */
    public function columns(): array;

    /** Permission that unmasks sensitive columns (e.g. patients.export.sensitive); null = they are always masked. */
    public function sensitivePermission(): ?string;

    /** @return LazyCollection<int, array<string, scalar|null>> one assoc row per record, keyed by column key; streamed, never loaded whole */
    public function rows(ReportContext $context): LazyCollection;
}
