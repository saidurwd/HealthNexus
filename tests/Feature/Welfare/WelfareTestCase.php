<?php

namespace Tests\Feature\Welfare;

use App\Models\Billing\BillingInvoice;
use App\Models\Patient;
use App\Models\User;
use App\Models\Welfare\WfAssessment;
use App\Models\Welfare\WfFund;
use App\Models\Welfare\WfGrant;
use App\Services\Welfare\AssessmentService;
use App\Services\Welfare\FundService;
use App\Services\Welfare\GrantService;
use Spatie\Permission\Models\Permission;
use Tests\Feature\Insurance\InsuranceTestCase;

/** A hospital with billing fixtures (the Insurance ones: patients and finalised invoices) plus staff who hold exactly the welfare rights a test names. */
abstract class WelfareTestCase extends InsuranceTestCase
{
    /** @param list<string> $permissions */
    protected function actor(array $permissions): User
    {
        $user = User::factory()->create();
        $user->organizations()->attach($this->organization->id, ['access_level' => 'admin']);
        $user->branches()->attach($this->branch->id, ['access_level' => 'manager', 'organization_id' => $this->organization->id]);
        foreach ($permissions as $p) {
            Permission::findOrCreate($p, 'web');
            $user->givePermissionTo($p);
        }

        return $user;
    }

    protected function fund(array $o = []): WfFund
    {
        return app(FundService::class)->createFund($this->organization->id, $o + ['name' => 'Fund '.fake()->unique()->numerify('####'), 'kind' => 'donation']);
    }

    protected function fundWith(string $amount, array $o = []): WfFund
    {
        $fund = $this->fund($o);
        app(FundService::class)->recordDonation($fund, $amount, null, $this->actor([]));

        return $fund;
    }

    /** An approved assessment: income 2,000 for 4 people (500 each) → free, 100%. */
    protected function approvedAssessment(?Patient $patient = null, array $d = []): WfAssessment
    {
        $patient ??= $this->patient();
        $assessor = $this->actor([]);
        $svc = app(AssessmentService::class);
        $a = $svc->start($patient, $d + ['monthly_income' => 2000, 'household_size' => 4, 'evidence_note' => 'Home visit by the social worker, no earner in work.'], $assessor);
        $svc->submit($a, $assessor);

        return $svc->decide($a->refresh(), $this->actor([]), true, 'Agreed.');
    }

    protected function approvedGrant(WfFund $fund, ?Patient $patient = null, string $cap = '5000.00', ?int $percent = 100): WfGrant
    {
        $a = $this->approvedAssessment($patient);
        $svc = app(GrantService::class);
        $g = $svc->request($a, $fund, $cap, $percent, 'Surgery', $this->actor([]));

        return $svc->approve($g, $this->actor([]));
    }

    protected function bill(Patient $patient, string $gross = '3000.00'): BillingInvoice
    {
        return $this->invoice($patient, [['OPD', 'CONSULT', $gross]]);
    }
}
