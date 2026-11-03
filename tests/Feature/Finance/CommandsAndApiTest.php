<?php

namespace Tests\Feature\Finance;

use App\Models\Finance\Journal;
use App\Models\Organization;
use App\Services\TenantContextResolver;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class CommandsAndApiTest extends FinanceTestCase
{
    public function test_finance_post_catches_up_what_the_event_missed(): void
    {
        $invoice = $this->bill(null, [['CONSULT', '300.00']], null, false);
        // finalised without the event (an import, a script, a listener that was off): the books never saw it
        DB::table('billing_invoices')->where('id', $invoice->id)->update(['status' => 'finalized', 'invoice_number' => 'INV-MISSED', 'finalized_at' => now()]);
        $this->assertSame(0, Journal::query()->count());
        $this->assertSame(0, Artisan::call('finance:post', ['--organization' => $this->organization->id]));
        $this->assertStringContainsString('1 missed', Artisan::output());
        $this->assertSame(1, Journal::query()->where(['status' => 'posted', 'source_type' => 'invoice'])->count());
        Artisan::call('finance:post');
        $this->assertSame(1, Journal::query()->count(), 'running it again changes nothing');
        $this->assertSame(0, Artisan::call('finance:check'));
    }

    /** @return array<string,string> a staff token for a user with these rights */
    private function asStaff(array $permissions): array
    {
        $user = $this->actor($permissions);
        session()->put(['tenant_organization_id' => $this->organization->id, 'tenant_branch_id' => $this->branch->id]);
        app(TenantContextResolver::class)->setOrganizationId($this->organization->id);

        return ['Authorization' => 'Bearer '.$user->createToken('t')->plainTextToken, 'X-Organization-Id' => (string) $this->organization->id];
    }

    public function test_read_only_api_needs_a_login_and_the_right_permission(): void
    {
        $this->bill(null, [['CONSULT', '500.00']]);
        foreach (['trial-balance', 'income-statement', 'balance-sheet', 'receivables', 'payables', 'reconciliation'] as $path) {
            $this->getJson("/api/v1/finance/$path")->assertUnauthorized();
        }
        $h = $this->asStaff(['finance.ledger.view']);
        $r = $this->getJson('/api/v1/finance/trial-balance?from=2026-09-01&to=2026-09-30', $h)->assertOk()->assertJsonPath('success', true);
        $this->assertTrue($r->json('meta.balanced'));
        $this->assertContains('1210', array_column($r->json('data'), 'code'));
        $this->getJson('/api/v1/finance/income-statement', $h)->assertForbidden();
        $h = $this->asStaff(['finance.report.view', 'finance.receivable.view', 'finance.payable.view', 'finance.posting.view']);
        $this->getJson('/api/v1/finance/income-statement?from=2026-09-01&to=2026-09-30', $h)->assertOk()->assertJsonPath('data.total_revenue', '500.00');
        $this->getJson('/api/v1/finance/balance-sheet', $h)->assertOk()->assertJsonPath('data.balanced', true);
        $this->getJson('/api/v1/finance/receivables', $h)->assertOk()->assertJsonPath('meta.totals.total', '500.00');
        $this->getJson('/api/v1/finance/payables', $h)->assertOk();
        $this->getJson('/api/v1/finance/reconciliation?from=2026-09-01&to=2026-09-30', $h)->assertOk()->assertJsonPath('meta.in_balance', true);
        $this->getJson('/api/v1/finance/income-statement?from=nonsense', $h)->assertStatus(422);
    }

    public function test_the_hospital_is_the_header_and_only_one_the_user_belongs_to(): void
    {
        $this->bill(null, [['CONSULT', '500.00']]);
        $other = Organization::factory()->create();
        $h = $this->asStaff(['finance.ledger.view']);
        $r = $this->getJson('/api/v1/finance/trial-balance?organization_id='.$other->id.'&from=2026-09-01&to=2026-09-30', $h)->assertOk();
        $this->assertContains('1210', array_column($r->json('data'), 'code'), 'a query parameter changes nothing');
        $this->getJson('/api/v1/finance/trial-balance', ['X-Organization-Id' => (string) $other->id] + $h)->assertForbidden();
        unset($h['X-Organization-Id']);
        app(TenantContextResolver::class)->setOrganizationId($this->organization->id); // a stale value from an earlier request must not leak in
        $this->getJson('/api/v1/finance/trial-balance', ['X-Organization-Id' => '0'] + $h)->assertStatus(422);
    }
}
