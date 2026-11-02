<?php

namespace App\Services\Demo\History\Modules;

use App\Models\Billing\BillingAdjustment;
use App\Models\Billing\BillingInvoice;
use App\Models\Patient;
use App\Models\User;
use App\Models\Welfare\WfFund;
use App\Models\Welfare\WfGrant;
use App\Services\Billing\AdjustmentService;
use App\Services\Demo\History\DemoHistoryModule;
use App\Services\Demo\History\HistoryContext;
use App\Services\Finance\FiscalService;
use App\Services\Welfare\ApplicationService;
use App\Services\Welfare\AssessmentService;
use App\Services\Welfare\FundService;
use App\Services\Welfare\GrantService;
use App\Services\Welfare\WelfareCheck;
use App\Services\Welfare\WelfareInstaller;
use Carbon\CarbonImmutable;
use Throwable;

/**
 * Patient welfare, six months. The funds are opened with a gift and then fed through the months by a few dozen donors (some anonymous); the hospital's own fund gets a monthly allocation. Patients whose bills stayed unpaid are
 * visited and assessed by a welfare officer, the committee decides (a different person), most are approved, the officer asks for a grant, a second person approves it against what the fund has free, the officer asks Billing to
 * take the amount off the bill, and Billing's accountant approves — which is when the fund is charged. A few are turned down, a few wait for a decision and a few discounts wait for Billing, so the waiting lists are not empty.
 * Everything is queued with its own time and run oldest first, so the ledger reads as it would have happened. It runs after Finance has posted the bills (Finance posts a bill from its totals, so a bill already cut to nothing
 * would have no entry); a month Finance has already closed is reopened by the finance manager to post the discount approved in it, and closed again, as an accountant would.
 */
class WelfareHistory implements DemoHistoryModule
{
    private const DONORS = [['Alhaj Abdul Karim', 'individual'], ['Mrs Rokeya Begum', 'individual'], ['Md Rafiqul Islam', 'individual'], ['Nasreen Sultana', 'individual'], ['Abul Hashem', 'individual'], ['Shirin Akter', 'individual'], ['Mehedi Hasan', 'individual'],
        ['Delta Textiles Ltd (demo)', 'company'], ['Padma Pharma Ltd (demo)', 'company'], ['Meghna Foods Ltd (demo)', 'company'], ['Surma Tea Estate (demo)', 'company'], ['Rotary Club (demo)', 'organisation'], ['Mosque committee (demo)', 'organisation'],
        ['Alumni association (demo)', 'organisation'], ['Hossain Family Trust (demo)', 'organisation']];

    private const OCCUPATIONS = ['Day labourer', 'Rickshaw puller', 'Garment worker', 'Small farmer', 'Street vendor', 'Domestic worker', 'Driver', 'Tea garden worker', 'Fisherman', 'Unemployed'];

    public function __construct(private readonly WelfareInstaller $installer, private readonly FundService $funds, private readonly AssessmentService $assessments, private readonly GrantService $grants, private readonly ApplicationService $applications,
        private readonly AdjustmentService $adjustments, private readonly WelfareCheck $check, private readonly FiscalService $fiscal) {}

    public function name(): string
    {
        return 'Patient welfare';
    }

    public function module(): string
    {
        return 'welfare';
    }

    public function run(HistoryContext $context): string
    {
        $org = $context->organization->id;
        $officer = $context->staff('welfare_officer')->first();
        $committee = $context->staff('welfare_committee_member')->first();
        $manager = $context->staff('welfare_manager')->first();
        $accountant = $context->staff('accountant')->first();
        $finance = $context->staff('finance_manager')->first();
        $context->at($context->from->setTime(9, 0), fn () => $this->installer->install($org));
        $funds = WfFund::query()->where('organization_id', $org)->get()->keyBy('kind');
        $donors = collect(self::DONORS)->map(fn ($d) => $context->at($context->from->setTime(9, 5), fn () => $this->funds->donor($org, ['name' => $d[0], 'kind' => $d[1], 'phone' => '017'.mt_rand(10000000, 99999999)])));
        $anonymous = $context->at($context->from->setTime(9, 6), fn () => $this->funds->donor($org, ['name' => 'Anonymous giver', 'kind' => 'individual', 'anonymous' => true]));

        $queue = [];
        $push = function (CarbonImmutable $at, callable $step) use (&$queue, $context) {
            if ($at->lte($context->to->setTime(18, 0))) {
                $queue[] = [$at, $step];
            }
        };
        $made = ['donations' => 0, 'assessed' => 0, 'approved' => 0, 'turned_down' => 0, 'grants' => 0, 'applied' => 0, 'waiting_billing' => 0, 'skipped' => 0];

        // money: an opening gift in each fund, then gifts through the months, and the hospital's own allocation on the first of each month
        foreach ([['zakat', 150000], ['donation', 100000], ['hospital', 200000]] as [$kind, $amount]) {
            $push($context->from->setTime(9, 30), function () use ($funds, $kind, $amount, $donors, $manager, &$made) {
                $this->funds->recordDonation($funds[$kind], $amount, $kind === 'hospital' ? null : $donors->first(), $manager, null, 'Opening gift');
                $made['donations']++;
            });
        }
        foreach ($context->days() as $day) {
            if ($day->day === 1) {
                $push($day->setTime(9, 0), function () use ($funds, $manager, &$made) {
                    $this->funds->recordDonation($funds['hospital'], 60000, null, $manager, null, 'Monthly allocation from the hospital budget');
                    $made['donations']++;
                });
            }
            if ($context->chance(22)) {
                $kind = $context->pick(['zakat', 'zakat', 'donation', 'donation', 'donation']);
                $amount = $context->pick([2000, 5000, 5000, 10000, 10000, 25000, 50000]);
                $push($day->setTime(mt_rand(10, 16), mt_rand(0, 59)), function () use ($funds, $kind, $amount, $donors, $anonymous, $manager, $context, &$made) {
                    $donor = $context->chance(25) ? $anonymous : $context->pick($donors);
                    $this->funds->recordDonation($funds[$kind], $amount, $donor, $manager, $context->chance(50) ? 'Cash' : 'bKash '.mt_rand(1000000, 9999999));
                    $made['donations']++;
                });
            }
        }

        // people: patients whose bills were not paid, one case each, oldest bills first
        $bills = BillingInvoice::query()->where('organization_id', $org)->whereIn('status', ['finalized', 'partially_paid'])->where('due_amount', '>=', 800)->whereBetween('invoice_date', [$context->from->toDateString(), $context->to->subDays(6)->toDateString()])
            ->orderBy('invoice_date')->get(['id', 'patient_id', 'invoice_date', 'due_amount', 'grand_total'])->unique('patient_id')->values();
        $every = max(1, intdiv($bills->count(), 70));
        foreach ($bills->filter(fn ($b, $i) => $i % $every === 0)->take(70) as $bill) {
            $start = CarbonImmutable::parse($bill->invoice_date)->addDays(mt_rand(0, 2))->setTime(mt_rand(9, 15), mt_rand(0, 59));
            $push($start, function () use ($context, $bill, $start, $officer, $committee, $accountant, $finance, $org, $funds, $push, &$made) {
                $patient = Patient::query()->find($bill->patient_id);
                $size = mt_rand(2, 7);
                $perHead = $context->chance(45) ? mt_rand(500, 2900) : ($context->chance(60) ? mt_rand(3000, 5900) : mt_rand(6000, 14000));
                $factors = array_values(array_filter(['no_earner', 'widow_headed', 'disabled_member', 'elderly_alone', 'orphan', 'long_illness'], fn () => $context->chance(12)));
                $a = $this->assessments->start($patient, ['monthly_income' => $perHead * $size, 'household_size' => $size, 'occupation' => $context->pick(self::OCCUPATIONS), 'factors' => $factors,
                    'evidence_note' => $context->pick(['Home visit by the social worker.', 'Letter from the ward councillor seen.', 'Union parishad certificate seen.', 'Interview at the bedside; neighbours confirmed.'])], $officer);
                $made['assessed']++;
                $push($start->addHours(3), function () use ($a, $officer, $start, $committee, $accountant, $finance, $org, $funds, $bill, $push, $context, &$made) {
                    $this->assessments->submit($a->refresh(), $officer);
                    if ($context->chance(8)) {
                        return; // left waiting for the committee
                    }
                    $push($start->addDay()->setTime(11, mt_rand(0, 50)), function () use ($a, $committee, $officer, $start, $accountant, $finance, $org, $funds, $bill, $push, $context, &$made) {
                        if ($context->chance(12)) {
                            $this->assessments->decide($a->refresh(), $committee, false, $context->pick(['Income is higher than stated.', 'Another relative can pay.', 'Already covered by an employer scheme.']));
                            $made['turned_down']++;

                            return;
                        }
                        $a = $this->assessments->decide($a->refresh(), $committee, true, 'Agreed by the committee.');
                        $made['approved']++;
                        if ($a->category === 'none') {
                            return;
                        }
                        $push($start->addDays(2)->setTime(10, mt_rand(0, 50)), function () use ($a, $officer, $committee, $start, $accountant, $finance, $org, $funds, $bill, $push, $context, &$made) {
                            $fund = $a->category === 'free' ? ($context->chance(60) ? $funds['zakat'] : $funds['hospital']) : ($context->chance(55) ? $funds['donation'] : $funds['hospital']);
                            $cap = min((float) ($fund->per_grant_cap ?? 20000), max(1000.0, round((float) $bill->grand_total * 1.5 / 500) * 500));
                            $g = $this->grants->request($a->refresh(), $fund->refresh(), $cap, null, 'Treatment bill', $officer);
                            $made['grants']++;
                            $push($start->addDays(3)->setTime(11, mt_rand(0, 50)), function () use ($g, $committee, $officer, $start, $accountant, $finance, $org, $bill, $push, $context, &$made) {
                                if ($context->chance(6)) {
                                    $this->grants->reject($g->refresh(), $committee, 'The fund is reserved for patients with no other support.');

                                    return;
                                }
                                $g = $this->grants->approve($g->refresh(), $committee, 'Approved.');
                                $push($start->addDays(4)->setTime(12, mt_rand(0, 50)), function () use ($g, $officer, $start, $accountant, $finance, $org, $bill, $push, $context, &$made) {
                                    $invoice = BillingInvoice::query()->find($bill->id);
                                    $app = $this->applications->apply($g->refresh(), $invoice, null, $officer);
                                    if ($context->chance(10)) {
                                        $made['waiting_billing']++;

                                        return; // Billing has not approved it yet
                                    }
                                    $push($start->addDays(5 + mt_rand(0, 1))->setTime(10, mt_rand(0, 50)), function () use ($app, $accountant, $finance, $org, &$made) {
                                        $this->inOpenMonth($org, $finance, fn () => $this->adjustments->approve(BillingAdjustment::query()->findOrFail($app->adjustment_id), $accountant, 'Welfare grant.'));
                                        $made['applied']++;
                                    });
                                });
                            });
                        });
                    });
                });
            });
        }

        // run everything oldest first; a step that cannot be done (a bill paid since, a fund short) is skipped, not forced
        $guard = 0;
        while ($queue && $guard++ < 5000) {
            usort($queue, fn ($x, $y) => $x[0] <=> $y[0]);
            [$at, $step] = array_shift($queue);
            try {
                $context->at($at, $step);
            } catch (Throwable) {
                $made['skipped']++;
            }
        }
        $context->at($context->to->setTime(18, 0), fn () => $this->check->run($org));

        return sprintf('%d funds, %d gifts received, %d patients assessed (%d approved, %d turned down), %d grants asked, %d discounts approved by Billing, %d waiting for Billing, %d steps skipped',
            WfFund::query()->where('organization_id', $org)->count(), $made['donations'], $made['assessed'], $made['approved'], $made['turned_down'], $made['grants'], WfGrant::query()->where('organization_id', $org)->where('spent_amount', '>', 0)->count(), $made['waiting_billing'], $made['skipped']);
    }

    /** Post a discount approved in a month Finance has closed: the finance manager reopens it, the discount is posted, and the month is closed again. */
    private function inOpenMonth(int $organizationId, User $finance, callable $do): void
    {
        $period = $this->fiscal->periodFor($organizationId, now());
        $reopen = $period && $period->status !== 'open';
        $reopen && $this->fiscal->unlock($period, $finance, 'Welfare discounts approved this month are posted');
        try {
            $do();
        } finally {
            $reopen && $this->fiscal->lock($period->refresh(), $finance, 'Month checked and closed');
        }
    }
}
