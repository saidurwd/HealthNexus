<?php

namespace App\Services\Insurance;

use App\Contracts\Integration\FinanceGateway;
use App\Models\Billing\BillingCategory;
use App\Models\Billing\BillingInsurancePolicy;
use App\Models\Billing\BillingInvoice;
use App\Models\Billing\BillingInvoiceItem;
use App\Models\Billing\BillingItem;
use App\Models\Diagnosis;
use App\Models\Encounter;
use App\Models\EncounterProcedure;
use App\Models\Insurance\InsuranceClaim;
use App\Models\Insurance\InsuranceClaimDocument;
use App\Models\Insurance\InsuranceClaimEvent;
use App\Models\Insurance\InsuranceClaimLine;
use App\Models\Insurance\InsurancePayer;
use App\Models\Insurance\InsurancePlan;
use App\Models\Insurance\InsurancePreauth;
use App\Models\Patient;
use App\Models\User;
use App\Services\Numbering\NumberSeriesService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Claims, built from FINALISED invoice lines (Insurance never re-prices), split by the coverage engine, scrubbed before they leave, and moved only
 * along the claim state machine. After submission nothing on a claim changes: a correction is a new version.
 */
class InsuranceClaimService
{
    public function __construct(
        private readonly NumberSeriesService $numbers,
        private readonly InsuranceCoverageEngine $engine,
        private readonly InsuranceMasterService $masters,
        private readonly InsurancePolicyService $policies,
        private readonly InsurancePreauthService $preauths,
        private readonly InsurancePolicySettings $settings,
        private readonly FinanceGateway $finance,
    ) {}

    public function generate(BillingInvoice $invoice, User $by, ?BillingInsurancePolicy $policy = null, ?InsuranceClaim $parent = null): InsuranceClaim
    {
        if (! in_array($invoice->status, ['finalized', 'partially_paid', 'paid'], true)) {
            throw ValidationException::withMessages(['invoice' => 'A claim is made from a finalised invoice.']);
        }
        $policy ??= $invoice->insurance_policy_id ? BillingInsurancePolicy::find($invoice->insurance_policy_id) : $this->policies->activeFor(Patient::findOrFail($invoice->patient_id), Carbon::parse($invoice->invoice_date)->toDateString())->first();
        if (! $policy || ! $policy->payer_id || ! $policy->plan_id) {
            throw ValidationException::withMessages(['policy' => 'The invoice has no policy with a payer and a plan.']);
        }
        $open = InsuranceClaim::query()->where('invoice_id', $invoice->id)->where('payer_id', $policy->payer_id)->whereNotIn('status', ['cancelled'])->when($parent, fn ($q) => $q->whereKeyNot($parent->id))->exists();
        if ($open && ! $parent) {
            throw ValidationException::withMessages(['invoice' => 'This invoice already has a claim for this payer.']);
        }

        return DB::transaction(function () use ($invoice, $by, $policy, $parent) {
            $payer = InsurancePayer::findOrFail($policy->payer_id);
            $plan = InsurancePlan::findOrFail($policy->plan_id);
            $encounter = $invoice->encounter_id ? Encounter::find($invoice->encounter_id) : null;
            $date = Carbon::parse($invoice->invoice_date)->toDateString();
            $preauth = $this->preauths->usable($policy, $date, $encounter);

            $items = BillingInvoiceItem::query()->where('invoice_id', $invoice->id)->orderBy('id')->get();
            $lines = $items->map(fn (BillingInvoiceItem $item) => $this->lineInput($item, $payer, $plan, $date))->all();
            $state = $this->engine->state($policy, $plan) + ['preauth_remaining' => $preauth?->remaining()];
            $result = $this->engine->split($plan, $lines, $state);

            $claim = InsuranceClaim::create([
                'organization_id' => $invoice->organization_id, 'branch_id' => $invoice->branch_id, 'payer_id' => $payer->id, 'policy_id' => $policy->id, 'plan_id' => $plan->id,
                'corporate_id' => $payer->corporate_id, 'patient_id' => $invoice->patient_id, 'encounter_id' => $encounter?->id, 'invoice_id' => $invoice->id, 'preauth_id' => $preauth?->id,
                'parent_claim_id' => $parent?->id, 'version' => ($parent ? $parent->version : 0) + 1,
                'claim_number' => $parent ? $this->nextVersionNumber($parent) : $this->numbers->next('insurance.claim', $invoice->organization_id, null, prefix: 'CLM', format: '{PREFIX}-{YEAR}-{SEQ:8}', userId: $by->id),
                'claim_type' => $this->claimType($invoice, $encounter), 'status' => 'draft', 'service_date' => $date, 'diagnoses' => $this->diagnoses($encounter), 'procedures' => $this->procedures($encounter),
                'gross_amount' => $result['totals']['gross'], 'non_covered_amount' => $result['totals']['non_covered'], 'deductible_amount' => $result['totals']['deductible'], 'copay_amount' => $result['totals']['copay'],
                'payer_share' => $result['totals']['payer'], 'patient_share' => $result['totals']['patient'],
                'filing_deadline' => date('Y-m-d', strtotime("$date +".$this->masters->filingDays($payer).' days')), 'created_by' => $by->id,
            ]);
            foreach ($items as $item) {
                $r = $result['lines'][$item->id];
                InsuranceClaimLine::create([
                    'organization_id' => $claim->organization_id, 'claim_id' => $claim->id, 'invoice_item_id' => $item->id, 'billing_item_id' => $item->billing_item_id,
                    'item_code' => $lines[$items->search($item)]['item_code'], 'description' => $item->description, 'quantity' => $item->quantity, 'gross_amount' => $r['gross'], 'contract_amount' => $r['contract'],
                    'non_covered_amount' => $r['non_covered'], 'deductible_amount' => $r['deductible'], 'copay_amount' => $r['copay'], 'payer_share' => $r['payer'], 'patient_share' => $r['patient'], 'note' => $r['note'] ?: null,
                ]);
            }
            foreach (config("insurance.document_checklist.{$claim->claim_type}", []) as [$type, $required]) {
                InsuranceClaimDocument::create(['organization_id' => $claim->organization_id, 'claim_id' => $claim->id, 'document_type' => $type, 'required' => $required, 'received' => $type === 'invoice']);
            }
            if ($preauth && bccomp($result['totals']['preauth_used'], '0', 2) > 0) {
                $this->preauths->consume($preauth, $result['totals']['preauth_used']);
                $this->event($claim, 'preauth_consumed', $by, "From {$preauth->request_number}", null, null, $result['totals']['preauth_used']);
            }
            $this->event($claim, 'created', $by, $parent ? "Version {$claim->version} of {$parent->claim_number}" : 'Built from the finalised invoice', null, 'draft', $claim->payer_share);

            return $claim->load('lines');
        });
    }

    /** Runs the scrub rules. @return list<array{rule: string, message: string, hint: string}> */
    public function scrub(InsuranceClaim $claim): array
    {
        $errors = [];
        $add = function (string $rule, string $hint) use (&$errors) {
            $errors[] = ['rule' => $rule, 'message' => config("insurance.scrub_rules.$rule"), 'hint' => $hint];
        };
        $policy = $claim->policy_id ? BillingInsurancePolicy::find($claim->policy_id) : null;

        if (! $policy || ! $this->policies->isActiveOn($policy, $claim->service_date->toDateString())) {
            $add('policy_active', 'Check the policy dates against the visit date.');
        }
        if (empty($claim->diagnoses) || collect($claim->diagnoses)->every(fn ($d) => blank($d['code'] ?? null))) {
            $add('diagnosis_present', 'Record a coded diagnosis on the visit, then rebuild the claim.');
        }
        $needs = $claim->lines()->where('note', 'like', '%pre-authorization%')->exists() || ($claim->plan_id && $claim->claim_type === 'inpatient' && InsurancePlan::whereKey($claim->plan_id)->value('requires_preauth_for_admission'));
        $payer = InsurancePayer::find($claim->payer_id);
        $threshold = ($payer ? $payer->getAttribute('preauth_required_above') : null) ?? $this->settings->get('preauth')['required_above'];
        if (($needs || ($threshold !== null && bccomp((string) $claim->payer_share, (string) $threshold, 2) > 0)) && ! $claim->preauth_id) {
            $add('preauth_present', 'Request a pre-authorization, then rebuild the claim.');
        }
        if ($claim->filing_deadline && $claim->filing_deadline->lt(today())) {
            $add('within_filing_window', 'The deadline has passed; submit with the payer’s late-filing exception, if any.');
        }
        if ($claim->documents()->where('required', true)->where('received', false)->exists()) {
            $missing = $claim->documents()->where('required', true)->where('received', false)->pluck('document_type')->implode(', ');
            $add('documents_complete', "Attach: $missing.");
        }
        $net = DB::table('insurance_claim_lines')->where('claim_id', $claim->id)->selectRaw('sum(payer_share) p, sum(patient_share) t, sum(gross_amount) g, sum(contract_amount) c')->first();
        $invoiceTotal = BillingInvoice::whereKey($claim->invoice_id)->value('subtotal');
        if (bccomp(bcadd((string) $net->p, (string) $net->t, 2), (string) $net->c, 2) !== 0 || ($invoiceTotal !== null && bccomp((string) $net->g, (string) $invoiceTotal, 2) > 0)) {
            $add('lines_tie_to_invoice', 'Rebuild the claim from the invoice.');
        }
        $dup = InsuranceClaim::query()->where('invoice_id', $claim->invoice_id)->where('payer_id', $claim->payer_id)->whereKeyNot($claim->id)->whereNotIn('status', ['cancelled'])
            ->where(fn ($q) => $q->whereNull('parent_claim_id')->orWhere('parent_claim_id', '!=', $claim->id))->where('version', '>=', $claim->version)->exists();
        if ($dup) {
            $add('no_duplicate', 'Withdraw the other claim first.');
        }

        return $errors;
    }

    /** Scrubs the draft and, only if it is clean, makes it ready to submit. @return list<array{rule: string, message: string, hint: string}> the errors (empty = ready) */
    public function markReady(InsuranceClaim $claim, User $by): array
    {
        if ($claim->status !== 'draft') {
            throw ValidationException::withMessages(['claim' => 'Only a draft can be made ready.']);
        }
        $errors = $this->scrub($claim);
        $claim->update(['scrub_errors' => $errors ?: null]);
        if ($errors === []) {
            $this->transition($claim, 'ready', $by, 'Scrub passed');
        } else {
            $this->event($claim, 'scrub_failed', $by, count($errors).' problem(s) found');
        }

        return $errors;
    }

    public function transition(InsuranceClaim $claim, string $to, ?User $by = null, ?string $note = null, ?string $amount = null): InsuranceClaim
    {
        return DB::transaction(function () use ($claim, $to, $by, $note, $amount) {
            $locked = InsuranceClaim::query()->lockForUpdate()->findOrFail($claim->id);
            $from = $locked->status;
            if ($from === $to) {
                return $locked;
            }
            if (! in_array($to, config("insurance.claim_transitions.$from", []), true)) {
                throw ValidationException::withMessages(['claim' => "A claim that is $from cannot become $to."]);
            }
            $changes = ['status' => $to];
            if ($to === 'submitted' && ! $locked->submitted_at) {
                $changes['submitted_at'] = now();
            }
            if (in_array($to, ['closed', 'cancelled'], true)) {
                $changes['closed_at'] = now();
            }
            $locked->update($changes);
            $this->event($locked, 'status_changed', $by, $note, $from, $to, $amount);

            if ($to === 'cancelled') {
                $this->releasePreauth($locked);
            }
            if ($to === 'accepted') {
                // Receivable for Finance (null adapter until Phase 21); the aging report works without it.
                $this->finance->postReceivable(['source' => 'insurance_claim', 'claim_id' => $locked->id, 'payer_id' => $locked->payer_id, 'amount' => (string) $locked->payer_share, 'currency' => 'BDT']);
            }

            return $locked->refresh();
        });
    }

    /**
     * Coordination of benefits: after the primary payer has been billed, what the patient would owe goes to the patient's second policy (coverage
     * order 2). The secondary claim is built from the primary claim's patient share, line by line, and split by the second plan's own rules.
     */
    public function generateSecondary(InsuranceClaim $primary, User $by): InsuranceClaim
    {
        if (! in_array($primary->status, ['partially_paid', 'paid', 'denied', 'written_off'], true)) {
            throw ValidationException::withMessages(['claim' => 'Bill the second policy after the first payer has answered.']);
        }
        $policy = $this->policies->activeFor($primary->patient, $primary->service_date->toDateString())->first(fn ($p) => (int) $p->coverage_order === 2 && $p->payer_id && $p->plan_id)
            ?? throw ValidationException::withMessages(['policy' => 'The patient has no second policy that was active on the service date.']);
        if (InsuranceClaim::where('parent_claim_id', $primary->id)->where('payer_id', $policy->payer_id)->whereNotIn('status', ['cancelled'])->exists()) {
            throw ValidationException::withMessages(['claim' => 'The second policy has already been billed for this claim.']);
        }

        return DB::transaction(function () use ($primary, $by, $policy) {
            $payer = InsurancePayer::findOrFail($policy->payer_id);
            $plan = InsurancePlan::findOrFail($policy->plan_id);
            $source = InsuranceClaimLine::where('claim_id', $primary->id)->where('patient_share', '>', 0)->orderBy('id')->get();
            if ($source->isEmpty()) {
                throw ValidationException::withMessages(['claim' => 'There is nothing left to bill the second policy for.']);
            }
            $lines = $source->map(fn ($l) => ['key' => $l->id, 'item_code' => $l->item_code, 'description' => $l->description, 'quantity' => 1, 'gross' => (string) $l->patient_share, 'category' => null, 'item_type' => null])->all();
            $result = $this->engine->split($plan, $lines, $this->engine->state($policy, $plan));

            $claim = InsuranceClaim::create([
                'organization_id' => $primary->organization_id, 'branch_id' => $primary->branch_id, 'payer_id' => $payer->id, 'policy_id' => $policy->id, 'plan_id' => $plan->id, 'corporate_id' => $payer->corporate_id,
                'patient_id' => $primary->patient_id, 'encounter_id' => $primary->encounter_id, 'invoice_id' => $primary->invoice_id, 'parent_claim_id' => $primary->id, 'version' => 1,
                'claim_number' => $this->numbers->next('insurance.claim', $primary->organization_id, null, prefix: 'CLM', format: '{PREFIX}-{YEAR}-{SEQ:8}', userId: $by->id),
                'claim_type' => $primary->claim_type, 'status' => 'draft', 'service_date' => $primary->service_date, 'diagnoses' => $primary->diagnoses, 'procedures' => $primary->procedures,
                'gross_amount' => $result['totals']['gross'], 'non_covered_amount' => $result['totals']['non_covered'], 'deductible_amount' => $result['totals']['deductible'], 'copay_amount' => $result['totals']['copay'],
                'payer_share' => $result['totals']['payer'], 'patient_share' => $result['totals']['patient'], 'filing_deadline' => date('Y-m-d', strtotime($primary->service_date->toDateString().' +'.$this->masters->filingDays($payer).' days')), 'created_by' => $by->id,
            ]);
            foreach ($source as $l) {
                $r = $result['lines'][$l->id];
                InsuranceClaimLine::create([
                    'organization_id' => $claim->organization_id, 'claim_id' => $claim->id, 'invoice_item_id' => $l->invoice_item_id, 'billing_item_id' => $l->billing_item_id, 'item_code' => $l->item_code, 'description' => $l->description,
                    'quantity' => $l->quantity, 'gross_amount' => $r['gross'], 'contract_amount' => $r['contract'], 'non_covered_amount' => $r['non_covered'], 'deductible_amount' => $r['deductible'], 'copay_amount' => $r['copay'],
                    'payer_share' => $r['payer'], 'patient_share' => $r['patient'], 'note' => trim('Second policy. '.$r['note']),
                ]);
            }
            foreach (config("insurance.document_checklist.{$claim->claim_type}", []) as [$type, $required]) {
                InsuranceClaimDocument::create(['organization_id' => $claim->organization_id, 'claim_id' => $claim->id, 'document_type' => $type, 'required' => $required, 'received' => $type === 'invoice']);
            }
            $this->event($claim, 'created', $by, "Second policy, from {$primary->claim_number}", null, 'draft', $claim->payer_share);

            return $claim->load('lines');
        });
    }

    /** A claim that was rejected or denied is corrected as a NEW version in draft; the old one stays as evidence. */
    public function newVersion(InsuranceClaim $claim, User $by): InsuranceClaim
    {
        if (! in_array($claim->status, ['rejected', 'denied', 'queried'], true)) {
            throw ValidationException::withMessages(['claim' => 'A new version is made from a claim that was rejected, denied or queried.']);
        }
        $invoice = BillingInvoice::findOrFail($claim->invoice_id);
        $policy = $claim->policy_id ? BillingInsurancePolicy::find($claim->policy_id) : null;
        $new = $this->generate($invoice, $by, $policy, $claim);
        if ($claim->status === 'rejected') {
            $this->transition($claim, 'cancelled', $by, "Replaced by version {$new->version}");
        } else {
            $this->event($claim, 'new_version', $by, "Version {$new->version} created", null, null);
        }

        return $new;
    }

    public function event(InsuranceClaim $claim, string $type, ?User $by = null, ?string $note = null, ?string $from = null, ?string $to = null, ?string $amount = null): InsuranceClaimEvent
    {
        return InsuranceClaimEvent::create([
            'organization_id' => $claim->organization_id, 'claim_id' => $claim->id, 'event_type' => $type, 'from_status' => $from, 'to_status' => $to, 'note' => $note,
            'amount' => $amount, 'actor_id' => $by?->id, 'occurred_at' => now(),
        ]);
    }

    private function releasePreauth(InsuranceClaim $claim): void
    {
        if ($claim->preauth_id) {
            $used = InsuranceClaimEvent::where('claim_id', $claim->id)->where('event_type', 'preauth_consumed')->sum('amount');
            $used > 0 && $this->preauths->release(InsurancePreauth::findOrFail($claim->preauth_id), number_format((float) $used, 2, '.', ''));
        }
    }

    /** @return array<string, mixed> */
    private function lineInput(BillingInvoiceItem $item, InsurancePayer $payer, InsurancePlan $plan, string $date): array
    {
        $billingItem = $item->billing_item_id ? BillingItem::find($item->billing_item_id) : null;
        $category = $billingItem?->category_id ? BillingCategory::whereKey($billingItem->category_id)->value('code') : null;

        return [
            'key' => $item->id, 'item_code' => $billingItem?->item_code, 'category' => $category, 'item_type' => $billingItem?->item_type, 'description' => $item->description, 'quantity' => (int) $item->quantity,
            'gross' => (string) $item->net_amount, 'agreed_unit_rate' => $billingItem ? $this->masters->agreedRate($payer, $plan, $billingItem->id, $date) : null,
        ];
    }

    private function claimType(BillingInvoice $invoice, ?Encounter $encounter): string
    {
        return match (true) {
            $invoice->invoice_type === 'ipd' => 'inpatient',
            strcasecmp((string) $encounter?->encounter_type, 'Emergency') === 0 || strcasecmp((string) $encounter?->encounter_type, 'ER') === 0 => 'emergency',
            default => 'outpatient',
        };
    }

    /** @return list<array{code: ?string, system: ?string, description: string}> */
    private function diagnoses(?Encounter $encounter): array
    {
        return $encounter ? Diagnosis::query()->where('encounter_id', $encounter->id)->get()->map(fn ($d) => ['code' => $d->code, 'system' => $d->coding_system, 'description' => $d->description])->all() : [];
    }

    /** @return list<array{code: ?string, description: string}> */
    private function procedures(?Encounter $encounter): array
    {
        return $encounter ? EncounterProcedure::query()->where('encounter_id', $encounter->id)->get()->map(fn ($p) => ['code' => $p->procedure_code, 'description' => $p->procedure_name])->all() : [];
    }

    private function nextVersionNumber(InsuranceClaim $parent): string
    {
        return preg_replace('/-V\d+$/', '', $parent->claim_number).'-V'.($parent->version + 1);
    }
}
