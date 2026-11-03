<?php

namespace App\Services\Documents;

final class DocumentSource
{
    public function __construct(
        public readonly string $reference,       // stable, human-meaningful ref used for the print audit (receipt no, Rx no, patient public id)
        public readonly array $variables,
        public readonly ?int $patientId = null,
        public readonly ?int $branchId = null,
        public readonly bool $isVoid = false,
        public readonly ?string $qrPayload = null,
        public readonly ?string $barcodeText = null,
    ) {}
}
