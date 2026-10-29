<?php

namespace App\Services\Finance;

use App\Models\Finance\Journal;
use App\Models\Finance\TaxCode;
use App\Models\Finance\TaxEntry;

/**
 * The VAT/AIT register: one row for every posting that carried tax, written together with its journal so the register and the ledger cannot drift apart.
 * Rates are not here: the amounts come from the documents themselves (Billing's tax, the supplier invoice's tax), and tax codes are the accountant's setting.
 */
class TaxRegister
{
    public function record(int $organizationId, string $kind, string $date, string $sourceType, string|int $sourceId, string $base, string $tax, ?Journal $journal, ?string $documentNumber = null, ?string $partyType = null, ?int $partyId = null, ?string $partyName = null, ?string $partyTaxId = null): ?TaxEntry
    {
        if (bccomp($tax, '0', 2) === 0) {
            return null;
        }
        $period = $journal?->period_id;
        $code = TaxCode::query()->where(['organization_id' => $organizationId, 'kind' => $kind, 'active' => true])->orderBy('id')->first();

        return TaxEntry::query()->firstOrCreate(['organization_id' => $organizationId, 'source_type' => $sourceType, 'source_id' => (string) $sourceId, 'kind' => $kind], [
            'tax_code_id' => $code?->id, 'entry_date' => $date, 'period_id' => $period, 'document_number' => $documentNumber, 'party_type' => $partyType, 'party_id' => $partyId, 'party_name' => $partyName ? mb_substr($partyName, 0, 150) : null,
            'party_tax_id' => $partyTaxId, 'base_amount' => $base, 'tax_amount' => $tax, 'journal_id' => $journal?->id, 'created_at' => now(),
        ]);
    }
}
