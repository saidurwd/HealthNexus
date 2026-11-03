<?php

namespace App\Integrations\Finance;

use App\Contracts\Integration\FinanceGateway;
use App\Contracts\Integration\IntegrationResult;
use App\Models\Insurance\InsuranceClaim;
use App\Models\Inventory\ProcurementSupplierInvoice;
use App\Services\Finance\ChartService;
use App\Services\Finance\FinanceException;
use App\Services\Finance\JournalService;
use App\Services\Finance\PayableService;
use App\Services\Finance\PostingEngine;
use Illuminate\Validation\ValidationException;

/**
 * The real Finance adapter (replaces the null one): payroll journals, insurer receivables and supplier invoices handed over by the other modules become entries in the books.
 * When Finance is switched off for the hospital it answers "not enabled" exactly like the null adapter did, so the other modules behave as before.
 */
class LedgerFinanceGateway implements FinanceGateway
{
    public function __construct(
        private readonly PostingEngine $engine,
        private readonly JournalService $journals,
        private readonly ChartService $chart,
        private readonly PayableService $payables,
    ) {}

    private function on(?int $organizationId): bool
    {
        return $organizationId !== null && $this->engine->enabled($organizationId);
    }

    /** @param array<string,mixed> $journal */
    public function postJournal(array $journal): IntegrationResult
    {
        $org = (int) ($journal['organization_id'] ?? 0);
        if (! $this->on($org)) {
            return IntegrationResult::notEnabled('FinanceGateway');
        }
        try {
            $lines = array_map(function (array $l) use ($org) {
                $account = $this->chart->accountFor($org, 'payroll', (string) $l['account']);

                return ['account' => $account->id, 'debit' => (string) $l['debit'], 'credit' => (string) $l['credit'], 'memo' => $l['memo'] ?? null];
            }, $journal['lines']);
            $posted = $this->journals->postAuto($org, ['date' => $journal['date'] ?? now()->toDateString(), 'memo' => (string) $journal['memo'], 'source_type' => 'hr_payroll', 'source_id' => (string) $journal['source'], 'event_key' => 'approved'], $lines);

            return IntegrationResult::handled(['reference' => $posted->number]);
        } catch (FinanceException $e) {
            return IntegrationResult::refused('FinanceGateway', $e->getMessage());
        } catch (ValidationException $e) {
            return IntegrationResult::refused('FinanceGateway', implode(' ', array_merge(...array_values($e->errors()))));
        }
    }

    /** @param array<string,mixed> $receivable */
    public function postReceivable(array $receivable): IntegrationResult
    {
        $claim = isset($receivable['claim_id']) ? InsuranceClaim::query()->find($receivable['claim_id']) : null;
        if ($claim === null || ! $this->on($claim->organization_id)) {
            return IntegrationResult::notEnabled('FinanceGateway');
        }
        $journal = $this->engine->post('claim.accepted', $claim);

        return $journal ? IntegrationResult::handled(['reference' => $journal->number]) : IntegrationResult::refused('FinanceGateway', 'The insurer share could not be posted yet; see Finance > Posting problems.');
    }

    /** @param array<string,mixed> $payable */
    public function postPayable(array $payable): IntegrationResult
    {
        $org = (int) ($payable['organization_id'] ?? 0);
        if (! $this->on($org)) {
            return IntegrationResult::notEnabled('FinanceGateway');
        }
        $invoice = ProcurementSupplierInvoice::query()->where('organization_id', $org)->where('public_id', str_replace('inventory:', '', (string) ($payable['source'] ?? '')))->first();
        if ($invoice === null) {
            return IntegrationResult::refused('FinanceGateway', 'The supplier invoice was not found.');
        }
        try {
            $p = $this->payables->fromSupplierInvoice($invoice);

            return IntegrationResult::handled(['reference' => 'AP-'.$p->id]);
        } catch (FinanceException $e) {
            return IntegrationResult::refused('FinanceGateway', $e->getMessage());
        } catch (ValidationException $e) {
            return IntegrationResult::refused('FinanceGateway', implode(' ', array_merge(...array_values($e->errors()))));
        }
    }
}
