<?php

namespace App\Services\Ai\UseCases;

use App\Models\Ai\AiOutput;
use App\Models\Insurance\InsuranceClaim;
use App\Models\Insurance\InsurancePreauth;
use App\Models\User;
use App\Services\Ai\AiCall;
use App\Services\Ai\AiGateway;
use App\Services\Insurance\InsuranceClaimService;
use App\Services\ModuleManager;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Use case: what may hold a claim up. It does not judge the claim itself: the checks are the Insurance module's own scrub rules (read, never saved), put next to the documents, payer queries, pre-authorization and filing
 * deadline recorded on the claim. It says what is recorded. It never submits, changes, approves or rejects a claim.
 */
class ClaimCheck
{
    public function __construct(private readonly AiGateway $gateway, private readonly InsuranceClaimService $claims, private readonly ModuleManager $modules) {}

    public function check(User $user, string $claimNumber, ?int $organizationId = null): AiOutput
    {
        $org = $organizationId ?? (int) $user->organizations()->value('organizations.id');
        if (! $this->modules->isEnabled('insurance', $org)) {
            throw ValidationException::withMessages(['claim_number' => __('The Insurance module is not switched on for this hospital.')]);
        }
        if (! $user->can('insurance.claim.view') || ! $user->organizations()->where('organizations.id', $org)->exists()) {
            abort(403, __('You are not allowed to use AI on this claim.'));
        }
        $claim = InsuranceClaim::query()->where('organization_id', $org)->where('claim_number', trim($claimNumber))->first() ?? throw ValidationException::withMessages(['claim_number' => __('No claim has that number.')]);
        $tz = (string) config('ai.timezone', 'Asia/Dhaka');
        $today = CarbonImmutable::now($tz)->startOfDay();
        $date = fn ($d) => $d ? CarbonImmutable::parse((string) $d)->setTimezone($tz)->format('d M Y') : '—';
        $items = [];
        $items[] = ['ref' => 'claim:'.$claim->id, 'text' => __('Claim :n (:t) for service date :d, status :s, version :v.', ['n' => $claim->claim_number, 't' => str_replace('_', ' ', (string) $claim->claim_type), 'd' => $date($claim->service_date), 's' => str_replace('_', ' ', (string) $claim->status), 'v' => $claim->version]), 'section' => __('Claim'), 'kind' => 'claim'];
        $items[] = ['ref' => 'claim:amounts', 'text' => __('Gross :g, payer share :p, patient share :t.', ['g' => $claim->gross_amount, 'p' => $claim->payer_share, 't' => $claim->patient_share]), 'section' => __('Claim'), 'kind' => 'claim'];

        $errors = $this->claims->scrub($claim);
        foreach ($errors as $e) {
            $items[] = ['ref' => 'scrub:'.$e['rule'], 'text' => __('Check failed: :m.', ['m' => rtrim(__((string) $e['message']), '.')]), 'section' => __('Checks'), 'kind' => 'check'];
        }
        if ($errors === []) {
            $items[] = ['ref' => 'scrub:clean', 'text' => __('The Insurance module’s checks found no problem.'), 'section' => __('Checks'), 'kind' => 'check'];
        }

        if ($claim->filing_deadline) {
            $left = (int) $today->diffInDays(CarbonImmutable::parse((string) $claim->filing_deadline, $tz)->startOfDay(), false);
            $items[] = ['ref' => 'claim:deadline', 'text' => $left >= 0 ? __('Filing deadline :d (:n days left).', ['d' => $date($claim->filing_deadline), 'n' => $left]) : __('Filing deadline :d passed :n days ago.', ['d' => $date($claim->filing_deadline), 'n' => abs($left)]), 'section' => __('Claim'), 'kind' => 'claim'];
        }
        if ($claim->preauth_id) {
            $pre = InsurancePreauth::query()->where('organization_id', $org)->find($claim->preauth_id);
            if ($pre) {
                $items[] = ['ref' => 'preauth:'.$pre->id, 'text' => __('Pre-authorization :r is :s, valid :f to :t, authorised :a, used :c.', ['r' => $pre->request_number, 's' => str_replace('_', ' ', (string) $pre->status), 'f' => $date($pre->valid_from), 't' => $date($pre->valid_to), 'a' => $pre->authorized_amount ?? '0.00', 'c' => $pre->consumed_amount ?? '0.00']), 'section' => __('Claim'), 'kind' => 'claim'];
            }
        }
        $docs = DB::table('insurance_claim_documents')->where('claim_id', $claim->id)->where('required', true)->get(['received']);
        if ($docs->isNotEmpty()) {
            $items[] = ['ref' => 'claim:docs', 'text' => __('Documents: :r of :n required documents received.', ['r' => $docs->where('received', true)->count(), 'n' => $docs->count()]), 'section' => __('Documents and queries'), 'kind' => 'claim'];
        }
        $open = DB::table('insurance_claim_queries')->where('claim_id', $claim->id)->whereNull('answered_at')->get(['id', 'kind', 'reason_text', 'due_at']);
        if ($open->isNotEmpty()) {
            $items[] = ['ref' => 'claim:queries', 'text' => __(':n payer queries are waiting for an answer.', ['n' => $open->count()]), 'section' => __('Documents and queries'), 'kind' => 'claim'];
            foreach ($open->take(5) as $q) {
                $items[] = ['ref' => 'query:'.$q->id, 'text' => __('Payer query (:k), due :d: :r', ['k' => str_replace('_', ' ', (string) $q->kind), 'd' => $date($q->due_at), 'r' => $q->reason_text]), 'section' => __('Documents and queries'), 'kind' => 'query', 'untrusted' => true];
            }
        }

        return $this->gateway->run(new AiCall($org, $user, 'insurance_claim_check', 'claim_check', $items, 'claim_check', __('Claim check: :n', ['n' => $claim->claim_number]), $claim->patient_id ? (int) $claim->patient_id : null, app()->getLocale() === 'bn' ? 'bn' : 'en',
            __('Only what the Insurance module checks and what is recorded on the claim. A clean result does not mean the payer will pay.')));
    }
}
