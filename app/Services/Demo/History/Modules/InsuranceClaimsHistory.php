<?php

namespace App\Services\Demo\History\Modules;

use App\Models\Billing\BillingCorporate;
use App\Models\Billing\BillingInsurancePolicy;
use App\Models\Billing\BillingInvoice;
use App\Models\Insurance\InsuranceClaim;
use App\Models\Insurance\InsurancePayer;
use App\Models\Patient;
use App\Services\Demo\History\DemoHistoryModule;
use App\Services\Demo\History\HistoryContext;
use App\Services\Insurance\CorporateCreditService;
use App\Services\Insurance\InsuranceClaimService;
use App\Services\Insurance\InsuranceEligibilityService;
use App\Services\Insurance\InsurancePreauthService;
use App\Services\Insurance\InsuranceQueryService;
use App\Services\Insurance\InsuranceRemittanceService;
use App\Services\Insurance\InsuranceSubmissionService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Insurance, second half: the claims the invoice hook drafted live their lives — eligibility checked, pre-authorizations for big ones, scrubbed
 * and made ready, sent in weekly batches, then paid in full or in part, queried and answered, denied and appealed or written off, rejected and
 * re-made as a new version — plus the monthly corporate statements. Runs after Billing.
 */
class InsuranceClaimsHistory implements DemoHistoryModule
{
    public function __construct(
        private readonly InsuranceClaimService $claims,
        private readonly InsuranceSubmissionService $submissions,
        private readonly InsuranceQueryService $queries,
        private readonly InsuranceRemittanceService $remittances,
        private readonly InsuranceEligibilityService $eligibility,
        private readonly InsurancePreauthService $preauths,
        private readonly CorporateCreditService $corporate,
    ) {}

    public function name(): string
    {
        return 'Insurance — claims';
    }

    public function module(): string
    {
        return 'insurance';
    }

    public function run(HistoryContext $context): string
    {
        $orgId = $context->organization->id;
        $officers = $context->staff('insurance_officer');
        $manager = $context->staff('claims_manager')->first();
        $counts = ['claims' => 0, 'ready' => 0, 'submitted' => 0, 'paid' => 0, 'partial' => 0, 'queried' => 0, 'denied' => 0, 'appealed' => 0, 'written_off' => 0, 'versions' => 0, 'stuck' => 0, 'preauths' => 0, 'statements' => 0];
        Storage::disk('local')->makeDirectory('insurance/exports');

        // 1. readiness: eligibility, documents, scrub (pre-authorization when a big claim needs it)
        $ready = [];
        foreach (InsuranceClaim::query()->where('organization_id', $orgId)->where('status', 'draft')->orderBy('id')->get() as $claim) {
            $counts['claims']++;
            $officer = $context->pick($officers);
            $created = CarbonImmutable::parse($claim->created_at);
            $policy = BillingInsurancePolicy::findOrFail($claim->policy_id);
            $patient = Patient::findOrFail($claim->patient_id);
            $context->chance(60) && $context->at($created->addHours(1), fn () => $this->eligibility->verifyManually($policy, $patient, $officer, 'eligible', 'Confirmed with the payer'));

            $attempt = $this->prepare($context, $claim, $officer, $created->addDay(), $counts);
            if ($attempt) {
                $ready[] = $attempt;
            } else {
                $counts['stuck']++;
            }
        }

        // 2. weekly batches per payer
        $submitted = [];
        foreach (collect($ready)->groupBy(fn ($r) => $r['claim']->payer_id.'|'.CarbonImmutable::parse($r['ready_at'])->startOfWeek()->toDateString()) as $group) {
            $payer = InsurancePayer::findOrFail($group->first()['claim']->payer_id);
            $sendAt = CarbonImmutable::parse($group->max('ready_at'))->addDay();
            if ($sendAt->gt($context->to)) {
                continue;
            }
            $officer = $context->pick($officers);
            $batch = $context->at($sendAt, fn () => $this->submissions->exportBatch($payer, $group->map(fn ($r) => $r['claim']->refresh()), $officer));
            $context->at($sendAt->addDay(), fn () => $this->submissions->markSubmitted($batch, 'REF-'.$batch->batch_number, $officer));
            foreach ($group as $r) {
                $submitted[] = ['claim' => $r['claim'], 'at' => $sendAt->addDay()];
                $counts['submitted']++;
            }
        }

        // 3. the payers answer
        $payments = [];
        foreach ($submitted as ['claim' => $claim, 'at' => $at]) {
            $this->outcome($context, $claim->refresh(), $at, $manager, $officers, $payments, $counts);
        }

        // 4. remittances: one lump per payer per week of payment
        foreach (collect($payments)->filter(fn ($p) => bccomp($p['paid'], '0', 2) > 0)->groupBy(fn ($p) => $p['claim']->payer_id.'|'.$p['on']->startOfWeek()->toDateString()) as $group) {
            $payer = InsurancePayer::findOrFail($group->first()['claim']->payer_id);
            $on = $group->max('on');
            if ($on->gt($context->to)) {
                continue;
            }
            $total = $group->reduce(fn ($c, $p) => bcadd($c, $p['paid'], 2), '0.00');
            $officer = $context->pick($officers);
            $remittance = $context->at($on, fn () => $this->remittances->record($payer, $total, $on->toDateString(), $officer, 'BANK-'.$on->format('ymd').'-'.$payer->id));
            foreach ($group as $p) {
                try {
                    $context->at($on->addHour(), fn () => $this->remittances->allocate($remittance->refresh(), $p['claim']->refresh(), $p['paid'], $officer, $p['adjustment'], $p['adjustment'] !== '0.00' ? 'Not covered by the contract' : null));
                    $p['full'] ? $counts['paid']++ : $counts['partial']++;
                } catch (ValidationException) {
                    // the invoice or claim moved on (e.g. already settled); the remittance keeps the unmatched part
                }
            }
        }

        // 5. second policies for part-paid and paid claims of patients that have one
        foreach (InsuranceClaim::query()->where('organization_id', $orgId)->whereIn('status', ['paid', 'partially_paid'])->where('patient_share', '>', 0)->orderBy('id')->limit(40)->get() as $claim) {
            if ($context->chance(25)) {
                try {
                    $context->at(CarbonImmutable::parse($claim->updated_at)->addDays(2), fn () => $this->claims->generateSecondary($claim->refresh(), $context->pick($officers)));
                } catch (ValidationException) {
                    // no second policy for this patient
                }
            }
        }

        // 6. corporate statements, a month at a time
        foreach (BillingCorporate::where('organization_id', $orgId)->get() as $client) {
            for ($m = $context->from->startOfMonth(); $m->endOfMonth()->lt($context->to->subDays(2)); $m = $m->addMonth()) {
                try {
                    $batch = $context->at($m->endOfMonth()->setTime(17, 0), fn () => $this->corporate->createBatch($client, $m->toDateString(), $m->endOfMonth()->toDateString(), $manager));
                    $batch = $context->at($m->endOfMonth()->setTime(17, 30), fn () => $this->corporate->issue($batch, $manager));
                    $payOn = $m->endOfMonth()->addDays(mt_rand(15, 40));
                    if ($context->chance(85) && $payOn->lt($context->to)) {
                        $context->at($payOn, fn () => $this->corporate->receivePayment($batch->refresh(), (string) $batch->total_amount, $manager, 'CHQ-'.$payOn->format('ymd')));
                    }
                    $counts['statements']++;
                } catch (ValidationException) {
                    // no invoices for this client in that month
                }
            }
        }

        return sprintf('%d claims: %d sent, %d paid in full, %d part-paid, %d queried, %d denied (%d appealed), %d written off, %d new versions, %d not yet ready; %d pre-authorizations; %d corporate statements',
            $counts['claims'], $counts['submitted'], $counts['paid'], $counts['partial'], $counts['queried'], $counts['denied'], $counts['appealed'], $counts['written_off'], $counts['versions'], $counts['stuck'], $counts['preauths'], $counts['statements']);
    }

    /** @return array{claim: InsuranceClaim, ready_at: CarbonImmutable}|null */
    private function prepare(HistoryContext $context, InsuranceClaim $claim, $officer, CarbonImmutable $at, array &$counts): ?array
    {
        $claim = $context->at($at, function () use ($claim) {
            if ($claim->diagnoses === null || $claim->diagnoses === []) {
                $claim->update(['diagnoses' => [['code' => 'R69', 'system' => 'ICD-10', 'description' => 'Illness, unspecified']]]);
            }
            $claim->documents()->where('received', false)->update(['received' => true]);

            return $claim->refresh();
        });
        $errors = $context->at($at, fn () => $this->claims->markReady($claim, $officer));
        if (collect($errors)->contains('rule', 'preauth_present')) {
            $policy = BillingInsurancePolicy::findOrFail($claim->policy_id);
            $patient = Patient::findOrFail($claim->patient_id);
            $pre = $context->at($at->subDays(2), fn () => $this->preauths->request($policy, $patient, $officer, 'Planned procedure', number_format((float) $claim->payer_share * 1.2, 2, '.', '')));
            $context->at($at->subDay(), fn () => $this->preauths->decide($pre, 'approved', number_format((float) $claim->payer_share * 1.2, 2, '.', ''), 'AUTH-'.$pre->id, $at->subDays(2)->toDateString(), $at->addDays(30)->toDateString()));
            $counts['preauths']++;
            $new = $context->at($at, fn () => $this->claims->generate(BillingInvoice::findOrFail($claim->invoice_id), $officer, $policy, $claim));
            $context->at($at, fn () => $this->claims->transition($claim->refresh(), 'cancelled', $officer, 'Rebuilt with the pre-authorization'));
            $new->documents()->update(['received' => true]);
            $claim = $new;
            $errors = $context->at($at, fn () => $this->claims->markReady($claim->refresh(), $officer));
        }
        if ($errors !== []) {
            return null;
        }
        $counts['ready']++;

        return ['claim' => $claim->refresh(), 'ready_at' => $at];
    }

    /** @param  list<array{claim: InsuranceClaim, on: CarbonImmutable, paid: string, adjustment: string, full: bool}>  $payments */
    private function outcome(HistoryContext $context, InsuranceClaim $claim, CarbonImmutable $sent, $manager, $officers, array &$payments, array &$counts): void
    {
        if ($claim->status !== 'submitted' || $sent->gt($context->to->subDays(20))) {
            return; // the newest claims are still with the payer
        }
        $officer = $context->pick($officers);
        $payer = (string) $claim->payer_share;
        $roll = mt_rand(1, 100);

        if ($roll <= 62) {
            $payments[] = ['claim' => $claim, 'on' => $sent->addDays(mt_rand(14, 45)), 'paid' => $payer, 'adjustment' => '0.00', 'full' => true];
        } elseif ($roll <= 72) {
            $adj = bcdiv(bcmul($payer, '0.10', 4), '1', 2);
            $payments[] = ['claim' => $claim, 'on' => $sent->addDays(mt_rand(20, 50)), 'paid' => bcsub(bcsub($payer, $adj, 2), bcdiv(bcmul($payer, '0.15', 4), '1', 2), 2), 'adjustment' => $adj, 'full' => false];
        } elseif ($roll <= 80) {
            $query = $context->at($sent->addDays(4), fn () => $this->queries->raise($claim, 'query', null, 'Please send the supporting report', $officer));
            $context->at($sent->addDays(6), fn () => $this->queries->answer($query, 'Report attached', $officer));
            $counts['queried']++;
            $payments[] = ['claim' => $claim, 'on' => $sent->addDays(mt_rand(25, 50)), 'paid' => $payer, 'adjustment' => '0.00', 'full' => true];
        } elseif ($roll <= 91) {
            $code = $context->pick(['D01', 'D02', 'D03', 'D04', 'D07', 'D08', 'D09']);
            $denial = $context->at($sent->addDays(14), fn () => $this->queries->raise($claim->refresh(), 'denial', $code, config("insurance.denial_reasons.$code.0"), $officer));
            $counts['denied']++;
            if ($context->chance(50)) {
                $appeal = $context->at($sent->addDays(17), fn () => $this->queries->appeal($claim->refresh(), 'The service was medically necessary and authorised on the day', $officer, $denial));
                $counts['appealed']++;
                $overturned = $context->chance(60);
                $context->at($sent->addDays(37), fn () => $this->queries->decideAppeal($appeal, $overturned, $overturned ? 'Accepted on review' : 'Denial maintained', $officer));
                if ($overturned) {
                    $payments[] = ['claim' => $claim, 'on' => $sent->addDays(mt_rand(45, 70)), 'paid' => $payer, 'adjustment' => '0.00', 'full' => true];

                    return;
                }
            }
            $this->writeOff($context, $claim, $sent->addDays(40), $officer, $manager, $counts);
        } elseif ($roll <= 95) {
            $context->at($sent->addDays(6), fn () => $this->queries->raise($claim, 'rejection', 'D08', 'Missing document', $officer));
            $new = $context->at($sent->addDays(8), fn () => $this->claims->newVersion($claim->refresh(), $officer));
            $counts['versions']++;
            $new->documents()->update(['received' => true]);
            $context->at($sent->addDays(8), fn () => $this->claims->markReady($new->refresh(), $officer));
        }
    }

    private function writeOff(HistoryContext $context, InsuranceClaim $claim, CarbonImmutable $at, $officer, $manager, array &$counts): void
    {
        try {
            $writeoff = $context->at($at, fn () => $this->remittances->requestWriteoff($claim->refresh(), $claim->outstanding(), 'Payer will not pay; recovery not possible', $officer));
            $context->at($at->addDay(), fn () => $this->remittances->decideWriteoff($writeoff, true, $manager));
            $counts['written_off']++;
        } catch (ValidationException) {
            // nothing outstanding
        }
    }
}
