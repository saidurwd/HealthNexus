<?php

namespace App\Services\Finance;

use App\Models\Billing\BillingCorporate;
use App\Models\Billing\BillingInvoice;
use App\Models\Finance\Journal;
use App\Models\Finance\Receivable;
use App\Models\Finance\ReceivableApplication;
use App\Models\Finance\Writeoff;
use App\Models\Insurance\InsurancePayer;
use App\Models\Patient;
use App\Models\User;
use App\Services\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Who owes the hospital what: one open item per invoice (or insurer claim), reduced by payments, refunds, credits and approved write-offs. */
class ReceivableService
{
    public function __construct(private readonly FinancePolicy $policy, private readonly ChartService $chart, private readonly JournalService $journals, private readonly AuditLogger $audit) {}

    public function open(int $organizationId, ?int $branchId, string $partyType, int $partyId, string $sourceType, int $sourceId, ?string $number, string $date, ?string $due, string $amount, ?int $costCenterId = null): Receivable
    {
        return Receivable::query()->firstOrCreate(['organization_id' => $organizationId, 'source_type' => $sourceType, 'source_id' => $sourceId], [
            'branch_id' => $branchId, 'party_type' => $partyType, 'party_id' => $partyId, 'document_number' => $number, 'document_date' => $date, 'due_date' => $due ?: $date, 'amount' => $amount, 'cost_center_id' => $costCenterId,
        ]);
    }

    /** Record that part of an open item was paid or removed. Repeating the same ($kind, $sourceType, $sourceId) changes nothing. A negative amount re-opens (a refund). */
    public function apply(Receivable $receivable, string $kind, string $amount, string $sourceType, string $sourceId, ?Journal $journal, string $date): Receivable
    {
        return DB::transaction(function () use ($receivable, $kind, $amount, $sourceType, $sourceId, $journal, $date) {
            $locked = Receivable::query()->lockForUpdate()->findOrFail($receivable->id);
            if (ReceivableApplication::query()->where(['receivable_id' => $locked->id, 'kind' => $kind, 'source_type' => $sourceType, 'source_id' => $sourceId])->exists()) {
                return $locked;
            }
            ReceivableApplication::create(['receivable_id' => $locked->id, 'kind' => $kind, 'amount' => $amount, 'source_type' => $sourceType, 'source_id' => $sourceId, 'journal_id' => $journal?->id, 'applied_on' => $date, 'created_at' => now()]);
            $isWriteoff = $kind === 'writeoff';
            $written = $isWriteoff ? bcadd((string) $locked->written_off, $amount, 2) : (string) $locked->written_off;
            $settled = $isWriteoff ? (string) $locked->settled : bcadd((string) $locked->settled, $amount, 2);
            $left = bcsub(bcsub((string) $locked->amount, $settled, 2), $written, 2);
            $locked->update(['settled' => $settled, 'written_off' => $written, 'status' => bccomp($left, '0', 2) <= 0 ? ($isWriteoff || bccomp($written, '0', 2) > 0 ? 'written_off' : 'settled') : 'open']);

            return $locked->refresh();
        });
    }

    public function outstanding(Receivable $r): string
    {
        return bcsub(bcsub((string) $r->amount, (string) $r->settled, 2), (string) $r->written_off, 2);
    }

    public function party(string $type, int $id): string
    {
        return match ($type) {
            'patient' => trim((string) Patient::query()->whereKey($id)->selectRaw("concat(coalesce(first_name,''),' ',coalesce(last_name,'')) as n")->value('n')) ?: "Patient #$id",
            'corporate' => (string) (BillingCorporate::query()->whereKey($id)->value('name') ?? "Corporate #$id"),
            'payer' => (string) (InsurancePayer::query()->whereKey($id)->value('name') ?? "Insurer #$id"),
            default => "#$id",
        };
    }

    /**
     * Ageing: open amounts by days past due on a date, per party. Buckets come from settings (default 0-30, 31-60, 61-90, over 90 days).
     *
     * @return array<string, mixed> buckets (labels), rows (one per party) and totals
     */
    public function ageing(int $organizationId, ?string $partyType = null, ?string $asOf = null): array
    {
        $asOf = CarbonImmutable::parse($asOf ?? now())->startOfDay();
        $edges = array_values(array_map('intval', (array) $this->policy->get('receivable.ageing_buckets', [30, 60, 90])));
        $labels = ['Not yet due'];
        $prev = 0;
        foreach ($edges as $e) {
            $labels[] = ($prev + 1).'-'.$e.' days';
            $prev = $e;
        }
        $labels[] = 'Over '.$prev.' days';
        $rows = [];
        foreach (Receivable::query()->where('organization_id', $organizationId)->where('status', 'open')->when($partyType, fn ($q) => $q->where('party_type', $partyType))->get() as $r) {
            $left = $this->outstanding($r);
            if (bccomp($left, '0', 2) <= 0) {
                continue;
            }
            $late = (int) $r->due_date->startOfDay()->diffInDays($asOf, false);
            $idx = 0;
            if ($late > 0) {
                $idx = count($edges) + 1;
                foreach ($edges as $i => $e) {
                    if ($late <= $e) {
                        $idx = $i + 1;
                        break;
                    }
                }
            }
            $key = $r->party_type.':'.$r->party_id;
            $rows[$key] ??= ['party_type' => $r->party_type, 'party_id' => $r->party_id, 'buckets' => array_fill(0, count($labels), '0.00'), 'total' => '0.00'];
            $rows[$key]['buckets'][$idx] = bcadd($rows[$key]['buckets'][$idx], $left, 2);
            $rows[$key]['total'] = bcadd($rows[$key]['total'], $left, 2);
        }
        $out = collect($rows)->map(fn ($row) => $row + ['name' => $this->party($row['party_type'], $row['party_id'])])->sortByDesc(fn ($row) => (float) $row['total'])->values();
        $totals = array_fill(0, count($labels), '0.00');
        $grand = '0.00';
        foreach ($out as $row) {
            foreach ($row['buckets'] as $i => $v) {
                $totals[$i] = bcadd($totals[$i], $v, 2);
            }
            $grand = bcadd($grand, $row['total'], 2);
        }

        return ['buckets' => $labels, 'rows' => $out, 'totals' => ['buckets' => $totals, 'total' => $grand]];
    }

    /** A party's statement: every open item with what was paid against it. @return Collection<int,Receivable> */
    public function statement(int $organizationId, string $partyType, int $partyId, bool $onlyOpen = false): Collection
    {
        return Receivable::query()->where(['organization_id' => $organizationId, 'party_type' => $partyType, 'party_id' => $partyId])->when($onlyOpen, fn ($q) => $q->where('status', 'open'))->orderBy('document_date')->get();
    }

    // ---- write-off with approval --------------------------------------------------------------------------------------------------------------

    public function requestWriteoff(Receivable $receivable, string $amount, string $reason, User $by): Writeoff
    {
        $left = $this->outstanding($receivable);
        if (bccomp($amount, '0', 2) <= 0 || bccomp($amount, $left, 2) > 0) {
            throw ValidationException::withMessages(['amount' => "A write-off must be above zero and no more than the $left still owed."]);
        }
        if (trim($reason) === '') {
            throw ValidationException::withMessages(['reason' => 'Say why the amount is written off.']);
        }
        $w = Writeoff::create(['organization_id' => $receivable->organization_id, 'receivable_id' => $receivable->id, 'amount' => $amount, 'reason' => mb_substr($reason, 0, 250), 'status' => 'requested', 'requested_by' => $by->id]);
        $this->audit->log('FIN_WRITEOFF_REQUESTED', Writeoff::class, $w->id, null, ['amount' => $amount, 'receivable' => $receivable->id]);

        return $w;
    }

    /** The approver must be a different person; approving posts bad debt against the receivable. */
    public function decideWriteoff(Writeoff $writeoff, bool $approve, User $by, ?string $note = null): Writeoff
    {
        if ($writeoff->status !== 'requested') {
            throw ValidationException::withMessages(['status' => 'This write-off was already decided.']);
        }
        if ($this->policy->bool('receivable.writeoff_second_approver', true) && $writeoff->requested_by === $by->id) {
            throw ValidationException::withMessages(['approver' => 'A write-off must be approved by someone other than the person who asked for it.']);
        }

        return DB::transaction(function () use ($writeoff, $approve, $by, $note) {
            $locked = Writeoff::query()->lockForUpdate()->findOrFail($writeoff->id);
            if ($locked->status !== 'requested') {
                throw ValidationException::withMessages(['status' => 'This write-off was already decided.']);
            }
            if (! $approve) {
                $locked->update(['status' => 'rejected', 'decided_by' => $by->id, 'decided_at' => now(), 'decision_note' => $note]);

                return $locked->refresh();
            }
            $receivable = Receivable::query()->findOrFail($locked->receivable_id);
            if (bccomp((string) $locked->amount, $this->outstanding($receivable), 2) > 0) {
                throw ValidationException::withMessages(['amount' => 'The item has been paid down since the request; ask again for a smaller amount.']);
            }
            $arRole = ['patient' => 'ar_patient', 'corporate' => 'ar_corporate', 'payer' => 'ar_insurer'][$receivable->party_type];
            $org = $receivable->organization_id;
            $journal = $this->journals->postAuto($org, ['date' => now()->toDateString(), 'memo' => 'Write-off of '.($receivable->document_number ?: 'receivable '.$receivable->id).': '.$locked->reason, 'source_type' => 'fin_writeoff', 'source_id' => (string) $locked->id, 'event_key' => 'approved'], [
                ['account' => $this->chart->accountFor($org, 'bad_debt')->id, 'debit' => (string) $locked->amount, 'credit' => '0'],
                ['account' => $this->chart->accountFor($org, $arRole)->id, 'debit' => '0', 'credit' => (string) $locked->amount, 'party_type' => $receivable->party_type, 'party_id' => $receivable->party_id],
            ], $by);
            $this->apply($receivable, 'writeoff', (string) $locked->amount, 'fin_writeoff', (string) $locked->id, $journal, now()->toDateString());
            $locked->update(['status' => 'approved', 'decided_by' => $by->id, 'decided_at' => now(), 'decision_note' => $note, 'journal_id' => $journal->id]);
            $this->audit->log('FIN_WRITEOFF_APPROVED', Writeoff::class, $locked->id, null, ['amount' => (string) $locked->amount, 'journal' => $journal->number]);

            return $locked->refresh();
        });
    }

    /** Party for a Billing invoice: a corporate client when there is one, otherwise the patient. @return array{0:string,1:int} */
    public function invoiceParty(BillingInvoice $invoice): array
    {
        return $invoice->corporate_id ? ['corporate', (int) $invoice->corporate_id] : ['patient', (int) $invoice->patient_id];
    }
}
