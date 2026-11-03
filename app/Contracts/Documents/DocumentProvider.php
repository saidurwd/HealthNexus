<?php

namespace App\Contracts\Documents;

use App\Services\Documents\DocumentSource;

/** Supplies the data for one printable document type. Must look the record up inside the given organization only. */
interface DocumentProvider
{
    public function type(): string;

    /** Permission needed to print this document. */
    public function permission(): string;

    /** letter-head paper: 'thermal80', 'a4', 'a5' or 'label'. */
    public function paper(): string;

    /** Financial documents get a DUPLICATE COPY mark and need a reason on every reprint. */
    public function isFinancial(): bool;

    public function resolve(string $reference, int $organizationId): ?DocumentSource;
}
