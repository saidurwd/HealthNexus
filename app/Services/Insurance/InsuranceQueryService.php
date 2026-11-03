<?php

namespace App\Services\Insurance;

use App\Models\Insurance\InsuranceAppeal;
use App\Models\Insurance\InsuranceClaim;
use App\Models\Insurance\InsuranceClaimQuery;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** What the payer sends back: queries, rejections and denials (with reason codes that drive the worklist), the answers, and appeals. */
class InsuranceQueryService
{
    public function __construct(private readonly InsuranceClaimService $claims) {}

    public function raise(InsuranceClaim $claim, string $kind, ?string $reasonCode, string $text, User $by, ?string $dueOn = null): InsuranceClaimQuery
    {
        $to = ['query' => 'queried', 'rejection' => 'rejected', 'denial' => 'denied'][$kind] ?? throw ValidationException::withMessages(['kind' => 'Not a known kind of reply.']);
        if ($reasonCode !== null && ! isset(config('insurance.denial_reasons')[$reasonCode])) {
            throw ValidationException::withMessages(['reason_code' => 'Not a known reason code.']);
        }
        if (in_array($kind, ['rejection', 'denial'], true) && $reasonCode === null) {
            throw ValidationException::withMessages(['reason_code' => 'A rejection or denial needs a reason code.']);
        }

        return DB::transaction(function () use ($claim, $kind, $to, $reasonCode, $text, $by, $dueOn) {
            $this->claims->transition($claim, $to, $by, $text);

            return InsuranceClaimQuery::create([
                'organization_id' => $claim->organization_id, 'claim_id' => $claim->id, 'kind' => $kind, 'reason_code' => $reasonCode, 'reason_text' => $text, 'raised_at' => now(),
                'due_at' => $dueOn ?? ($kind === 'query' ? now()->addDays(7) : null),
            ]);
        });
    }

    /** Answers a query; the claim goes back to the payer unchanged (the answer, not new lines). */
    public function answer(InsuranceClaimQuery $query, string $response, User $by): InsuranceClaimQuery
    {
        return DB::transaction(function () use ($query, $response, $by) {
            $locked = InsuranceClaimQuery::query()->lockForUpdate()->findOrFail($query->id);
            if ($locked->kind !== 'query' || $locked->status !== 'open') {
                throw ValidationException::withMessages(['query' => 'Only an open query can be answered.']);
            }
            $locked->update(['status' => 'answered', 'response' => $response, 'answered_at' => now(), 'answered_by' => $by->id]);
            $this->claims->transition(InsuranceClaim::findOrFail($locked->claim_id), 'submitted', $by, "Query answered: $response");

            return $locked->refresh();
        });
    }

    public function appeal(InsuranceClaim $claim, string $grounds, User $by, ?InsuranceClaimQuery $denial = null): InsuranceAppeal
    {
        if (strlen(trim($grounds)) < 10) {
            throw ValidationException::withMessages(['grounds' => 'Say why the denial is wrong.']);
        }

        return DB::transaction(function () use ($claim, $grounds, $by, $denial) {
            $this->claims->transition($claim, 'appealed', $by, $grounds);

            return InsuranceAppeal::create(['organization_id' => $claim->organization_id, 'claim_id' => $claim->id, 'query_id' => $denial?->id, 'grounds' => $grounds, 'filed_at' => now(), 'filed_by' => $by->id]);
        });
    }

    public function decideAppeal(InsuranceAppeal $appeal, bool $overturned, ?string $note, User $by): InsuranceAppeal
    {
        return DB::transaction(function () use ($appeal, $overturned, $note, $by) {
            $locked = InsuranceAppeal::query()->lockForUpdate()->findOrFail($appeal->id);
            if ($locked->status !== 'filed') {
                throw ValidationException::withMessages(['appeal' => 'This appeal was already decided.']);
            }
            $locked->update(['status' => $overturned ? 'overturned' : 'upheld', 'outcome_note' => $note, 'decided_at' => now()]);
            $this->claims->transition(InsuranceClaim::findOrFail($locked->claim_id), $overturned ? 'accepted' : 'denied', $by, $overturned ? 'Appeal succeeded' : 'Appeal rejected');

            return $locked->refresh();
        });
    }

    /** Open denials and rejections grouped by reason code, so the team works the biggest cause first. @return Collection<int, array{code: ?string, label: string, kind: string, count: int, amount: string, fix: string}> */
    public function worklist(int $organizationId): Collection
    {
        $rows = DB::table('insurance_claim_queries as q')->join('insurance_claims as c', 'c.id', '=', 'q.claim_id')->where('q.organization_id', $organizationId)
            ->whereIn('q.kind', ['denial', 'rejection'])->whereIn('c.status', ['denied', 'rejected'])
            ->selectRaw('q.reason_code, q.kind, count(*) as n, sum(c.payer_share) as amount')->groupBy('q.reason_code', 'q.kind')->orderByDesc('n')->get();

        return $rows->map(fn ($r) => [
            'code' => $r->reason_code, 'label' => config("insurance.denial_reasons.{$r->reason_code}.0", 'Unspecified'), 'kind' => $r->kind, 'count' => (int) $r->n,
            'amount' => number_format((float) $r->amount, 2, '.', ''), 'fix' => config("insurance.denial_reasons.{$r->reason_code}.2", ''),
        ]);
    }
}
