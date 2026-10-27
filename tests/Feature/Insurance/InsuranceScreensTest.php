<?php

namespace Tests\Feature\Insurance;

use App\Models\Billing\BillingCorporate;
use App\Models\Billing\BillingInsurancePolicy;
use App\Models\Branch;
use App\Models\Insurance\CorporateBatch;
use App\Models\Insurance\InsuranceClaim;
use App\Models\Insurance\InsuranceClaimQuery;
use App\Models\Insurance\InsurancePayer;
use App\Models\Insurance\InsurancePreauth;
use App\Models\Insurance\InsuranceRemittance;
use App\Models\Insurance\InsuranceSubmission;
use App\Models\Insurance\InsuranceWriteoff;
use App\Models\Organization;
use App\Models\User;
use App\Services\Demo\DemoAccess;
use App\Services\Insurance\InsuranceClaimService;
use App\Services\Insurance\InsurancePreauthService;
use App\Services\Insurance\InsuranceQueryService;
use App\Services\Insurance\InsuranceRemittanceService;
use App\Services\Insurance\InsuranceSubmissionService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\PermissionRegistrar;

class InsuranceScreensTest extends InsuranceTestCase
{
    /** @return array<string, mixed> */
    private function world(): array
    {
        $patient = $this->patient();
        $policy = $this->policy($patient);
        $claims = app(InsuranceClaimService::class);
        $claim = $claims->generate($this->invoice($patient, [['OPD', 'CONSULT', '1000.00']], $policy), $this->user);
        $claim->documents()->update(['received' => true]);
        $claims->markReady($claim, $this->user);
        $submissions = app(InsuranceSubmissionService::class);
        Storage::fake('local');
        $batch = $submissions->exportBatch($claim->payer, [$claim->refresh()], $this->user);
        $submissions->markSubmitted($batch, 'R-1', $this->user);
        $remittance = app(InsuranceRemittanceService::class)->record($claim->payer, '300.00', now()->toDateString(), $this->user, 'BANK-1');
        $preauth = app(InsurancePreauthService::class)->request($policy, $patient, $this->user, 'MRI brain', '4000.00');
        $denied = $claims->generate($this->invoice($this->patient(), [['OPD', 'CONSULT', '500.00']], $this->policy($this->patient())), $this->user);
        $writeoff = null;

        return compact('patient', 'policy', 'claim', 'batch', 'remittance', 'preauth', 'denied', 'writeoff');
    }

    public function test_every_insurance_screen_opens_for_the_super_user(): void
    {
        $w = $this->world();
        $corporate = BillingCorporate::create(['organization_id' => $this->organization->id, 'name' => 'Acme', 'code' => 'ACME1', 'credit_limit' => '1000', 'status' => 'active']);
        foreach (['', '/payers', '/policies', '/policies?q=POL', '/preauths', '/worklist', '/claims', '/claims?status=submitted', '/claims/'.$w['claim']->public_id, '/claims/'.$w['denied']->public_id, '/remittances', '/remittances/'.$w['remittance']->public_id, '/corporate'] as $path) {
            $this->get('/admin/insurance'.$path)->assertOk();
        }
        $this->get('/admin/insurance/submissions/'.$w['batch']->public_id.'/download')->assertOk();
        $this->assertNotNull($corporate);
    }

    public function test_the_forms_work_on_screen(): void
    {
        $b = '/admin/insurance';
        $this->post("$b/payers", ['code' => 'TPA1', 'name' => 'Metro TPA', 'payer_type' => 'tpa', 'submission_method' => 'file_export'])->assertRedirect()->assertSessionHas('success');
        $payer = InsurancePayer::where('code', 'TPA1')->firstOrFail();
        $this->post("$b/payers/{$payer->public_id}/plans", ['code' => 'GOLD', 'name' => 'Gold', 'copay_percent' => 10])->assertRedirect()->assertSessionHas('success');
        $plan = $payer->plans()->firstOrFail();
        $patient = $this->patient();
        $this->post("$b/policies", ['patient' => $patient->enterprise_patient_no, 'plan' => $plan->public_id, 'policy_number' => 'P-9', 'member_number' => 'M-9', 'effective_from' => now()->subMonth()->toDateString()])->assertRedirect()->assertSessionHas('success');
        $policy = BillingInsurancePolicy::firstOrFail();
        $this->post("$b/policies/{$policy->public_id}/verify", ['status' => 'eligible'])->assertRedirect();
        $this->post("$b/preauths", ['policy' => $policy->public_id, 'service_description' => 'CT scan', 'requested_amount' => 3000])->assertRedirect()->assertSessionHas('success');
        $preauth = InsurancePreauth::firstOrFail();
        $this->post("$b/preauths/{$preauth->public_id}/decide", ['status' => 'approved', 'authorized_amount' => 3000, 'payer_reference' => 'A1'])->assertRedirect()->assertSessionHas('success');

        $invoice = $this->invoice($patient, [['CT', 'RAD', '3000.00']], $policy);
        $this->post("$b/claims", ['invoice' => $invoice->invoice_number])->assertRedirect();
        $claim = InsuranceClaim::firstOrFail();
        $this->post("$b/claims/{$claim->public_id}/ready")->assertRedirect()->assertSessionHas('warning');
        foreach ($claim->documents()->where('received', false)->get() as $doc) {
            $this->post("$b/claims/{$claim->public_id}/documents/{$doc->id}")->assertRedirect();
        }
        $this->post("$b/claims/{$claim->public_id}/ready")->assertRedirect()->assertSessionHas('success');
        Storage::fake('local');
        $this->post("$b/claims/export", ['payer' => $payer->public_id, 'claims' => [$claim->public_id]])->assertRedirect()->assertSessionHas('success');
        $batch = InsuranceSubmission::firstOrFail();
        $this->post("$b/submissions/{$batch->public_id}/submitted", ['payer_reference' => 'REF9'])->assertRedirect()->assertSessionHas('success');
        $this->assertSame('submitted', $claim->refresh()->status);

        $this->post("$b/claims/{$claim->public_id}/reply", ['kind' => 'denial', 'reason_code' => 'D07', 'text' => 'Limit reached'])->assertRedirect();
        $this->post("$b/claims/{$claim->public_id}/writeoff", ['amount' => 2700, 'reason' => 'Annual limit reached'])->assertRedirect()->assertSessionHas('success');
        $this->assertSame(1, InsuranceWriteoff::count());
        $this->post("$b/remittances", ['payer' => $payer->public_id, 'amount' => 100, 'received_on' => now()->toDateString()])->assertRedirect();
        $this->assertSame(1, InsuranceRemittance::count());
    }

    private function routes(): array
    {
        $w = $this->world();
        app(InsuranceQueryService::class)->raise(InsuranceClaim::find($w['claim']->id), 'query', null, 'Need the note', $this->user);
        $query = InsuranceClaimQuery::firstOrFail();
        $w['claim']->refresh();
        $writeoff = InsuranceWriteoff::create(['organization_id' => $this->organization->id, 'claim_id' => $w['claim']->id, 'amount' => 10, 'reason' => 'x']);
        $b = '/admin/insurance';
        $doc = $w['denied']->documents()->first();
        $batch = CorporateBatch::create(['organization_id' => $this->organization->id, 'corporate_id' => BillingCorporate::create(['organization_id' => $this->organization->id, 'name' => 'Z', 'code' => 'Z1', 'status' => 'active'])->id, 'batch_number' => 'CRP-T', 'period_from' => now(), 'period_to' => now()]);

        return [
            ['GET', "$b/claims/{$w['claim']->public_id}"], ['POST', "$b/claims/{$w['claim']->public_id}/ready"], ['POST', "$b/claims/{$w['claim']->public_id}/rebuild"], ['POST', "$b/claims/{$w['claim']->public_id}/documents/{$doc->id}"],
            ['POST', "$b/claims/{$w['claim']->public_id}/version"], ['POST', "$b/claims/{$w['claim']->public_id}/secondary"], ['POST', "$b/claims/{$w['claim']->public_id}/cancel"], ['POST', "$b/claims/{$w['claim']->public_id}/reply"],
            ['POST', "$b/claims/{$w['claim']->public_id}/appeal"], ['POST', "$b/claims/{$w['claim']->public_id}/writeoff"], ['POST', "$b/queries/{$query->public_id}/answer"], ['GET', "$b/submissions/{$w['batch']->public_id}/download"],
            ['POST', "$b/submissions/{$w['batch']->public_id}/submitted"], ['GET', "$b/remittances/{$w['remittance']->public_id}"], ['POST', "$b/remittances/{$w['remittance']->public_id}/allocate"], ['POST', "$b/writeoffs/{$writeoff->public_id}/decide"],
            ['POST', "$b/preauths/{$w['preauth']->public_id}/decide"], ['POST', "$b/policies/{$w['policy']->public_id}/verify"], ['POST', "$b/corporate/batches/{$batch->public_id}/issue"], ['POST', "$b/corporate/batches/{$batch->public_id}/pay"],
            ['GET', "$b"], ['GET', "$b/claims"], ['GET', "$b/payers"], ['GET', "$b/policies"], ['GET', "$b/preauths"], ['GET', "$b/remittances"], ['GET', "$b/corporate"], ['GET', "$b/worklist"],
            ['POST', "$b/payers"], ['POST', "$b/claims"], ['POST', "$b/claims/export"], ['POST', "$b/remittances"], ['POST', "$b/preauths"], ['POST', "$b/policies"], ['POST', "$b/corporate/batches"],
        ];
    }

    public function test_another_organization_reaches_none_of_our_records_and_a_member_without_permission_is_refused_everywhere(): void
    {
        $routes = $this->routes();

        $otherOrg = Organization::factory()->create();
        $otherBranch = Branch::factory()->create(['organization_id' => $otherOrg->id]);
        $this->actingAs($this->staff(['super_admin'], $otherOrg, $otherBranch));
        $this->context($otherOrg, $otherBranch);
        $leaks = [];
        foreach ($routes as [$method, $uri]) {
            $status = $this->call($method, $uri, [], [], [], ['HTTP_ACCEPT' => 'application/json'])->getStatusCode();
            if (preg_match('#/[0-9A-Z]{26}#', $uri) && ! in_array($status, [403, 404], true)) {
                $leaks[] = "$method $uri → $status";
            }
        }
        $this->assertSame([], $leaks);

        $this->seed(PermissionSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->actingAs($this->staff([]));
        $this->context();
        $open = [];
        foreach ($routes as [$method, $uri]) {
            $status = $this->call($method, $uri, [], [], [], ['HTTP_ACCEPT' => 'application/json'])->getStatusCode();
            if ($status !== 403) {
                $open[] = "$method $uri → $status";
            }
        }
        $this->assertSame([], $open);
    }

    public function test_a_module_viewer_can_look_but_not_change(): void
    {
        $this->seed(PermissionSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        app(DemoAccess::class)->build($this->organization, $this->branch, 'x-pass-123');
        $viewer = User::where('email', 'insurance.viewer@'.strtolower($this->organization->code).'.example.invalid')->firstOrFail();
        $this->actingAs($viewer);

        $this->get('/admin/insurance')->assertOk();
        $this->get('/admin/insurance/claims')->assertOk();
        $this->post('/admin/insurance/remittances', ['payer' => 'x', 'amount' => 1, 'received_on' => now()->toDateString()])->assertForbidden();
        $this->post('/admin/insurance/payers', ['code' => 'X', 'name' => 'X', 'payer_type' => 'tpa', 'submission_method' => 'file_export'])->assertForbidden();
    }
}
