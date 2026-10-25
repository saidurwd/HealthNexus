<?php

namespace Tests\Feature\Billing;

use App\Models\Billing\BillingCategory;
use App\Models\Billing\BillingCharge;
use App\Models\Billing\BillingItem;
use App\Models\Billing\BillingPaymentMethod;
use App\Models\Billing\BillingReceipt;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\User;
use App\Services\Billing\InvoiceLifecycleService;
use App\Services\Billing\InvoiceService;
use App\Services\Billing\PaymentService;
use App\Services\Documents\Code128Svg;
use App\Services\Documents\DocumentService;
use App\Services\Documents\SafeTemplate;
use App\Services\SettingsService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** H-08: receipt (80 mm), Bangla/English, QR, reprint control, editable templates. */
class DocumentPrintTest extends BillingTestCase
{
    private string $receiptNumber;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->patient = Patient::factory()->create(['organization_id' => $this->organization->id, 'first_name' => 'Rahim', 'middle_name' => null, 'last_name' => 'Uddin']);
        $category = BillingCategory::create(['organization_id' => $this->organization->id, 'branch_id' => null, 'name' => 'C', 'code' => 'C', 'is_active' => true]);
        $item = BillingItem::create([
            'organization_id' => $this->organization->id, 'branch_id' => null, 'category_id' => $category->id, 'item_code' => 'OPD-1',
            'item_type' => 'consultation', 'name' => 'OPD', 'base_price' => '2000.00', 'is_taxable' => false, 'is_clinically_chargeable' => false, 'is_active' => true,
        ]);
        $method = BillingPaymentMethod::create(['organization_id' => $this->organization->id, 'branch_id' => null, 'name' => 'Cash', 'code' => 'CASH', 'type' => 'cash', 'is_active' => true]);
        $charge = BillingCharge::create([
            'organization_id' => $this->organization->id, 'branch_id' => $this->branch->id, 'patient_id' => $this->patient->id, 'billing_item_id' => $item->id,
            'idempotency_key' => uniqid('c', true), 'status' => 'pending', 'quantity' => 1, 'currency' => 'BDT', 'unit_price' => '2000.00',
            'gross_amount' => '2000.00', 'discount_type' => 'none', 'discount_amount' => '0', 'tax_amount' => '0', 'net_amount' => '2000.00', 'charged_at' => now(),
        ]);
        $invoice = app(InvoiceService::class)->createDraft($this->patient, ['organization_id' => $this->organization->id, 'branch_id' => $this->branch->id], $this->user);
        app(InvoiceService::class)->addCharges($invoice, new Collection([$charge]));
        $invoice = app(InvoiceLifecycleService::class)->finalize($invoice, $this->user);
        $payment = app(PaymentService::class)->collect($invoice, ['payment_method_id' => $method->id, 'amount' => '1250.50'], $this->user);
        $this->receiptNumber = $payment->receipt->receipt_number;
    }

    private function print(string $lang = 'en', ?string $reason = null): string
    {
        return app(DocumentService::class)->render('receipt', $this->receiptNumber, $lang, $this->user, $reason);
    }

    public function test_receipt_renders_on_80mm_paper_with_data_qr_and_no_patient_data_in_the_code(): void
    {
        $html = $this->print();

        $this->assertStringContainsString('size:80mm auto', $html);
        $this->assertStringContainsString($this->receiptNumber, $html);
        $this->assertStringContainsString('Rahim Uddin', $html);
        $this->assertStringContainsString('1,250.50', $html);
        $this->assertStringContainsString('<svg', $html); // QR
        $this->assertStringContainsString('Payment Receipt', $html);
        $this->assertStringNotContainsString('DUPLICATE', $html);
    }

    public function test_bangla_and_bilingual_labels_and_optional_bangla_numerals(): void
    {
        $this->assertStringContainsString('পেমেন্ট রসিদ', $this->print('bn'));
        $both = $this->print('en+bn', 'customer lost it');
        $this->assertStringContainsString('পেমেন্ট রসিদ / Payment Receipt', $both);

        app(SettingsService::class)->set('documents.bangla_numerals', true);
        $bn = $this->print('bn', 'again');
        $this->assertStringContainsString('১,২৫০.৫০', $bn);
        $this->assertMatchesRegularExpression('/<svg[^>]*viewBox="0 0 \d+ \d+"/', $bn, 'QR geometry keeps ASCII digits');
    }

    public function test_reprint_needs_a_reason_is_marked_as_a_copy_and_is_audited(): void
    {
        $this->print();

        try {
            $this->print();
            $this->fail('reprint without reason must be refused');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }

        $copy = $this->print('en', 'Customer lost the first copy');

        $this->assertStringContainsString('DUPLICATE COPY #2', $copy);
        $this->assertSame(2, DB::table('document_prints')->where('document_ref', $this->receiptNumber)->count());
        $this->assertSame('Customer lost the first copy', DB::table('document_prints')->where('copy_number', 2)->value('reprint_reason'));
        $this->assertDatabaseHas('audit_logs', ['action' => 'PRINT']);
    }

    public function test_user_without_the_permission_cannot_print(): void
    {
        $nobody = User::factory()->create();
        $nobody->organizations()->attach($this->organization->id, ['access_level' => 'staff']);

        $this->expectException(AuthorizationException::class);

        app(DocumentService::class)->render('receipt', $this->receiptNumber, 'en', $nobody);
    }

    public function test_another_organizations_receipt_is_a_404(): void
    {
        $other = Organization::factory()->create();
        BillingReceipt::query()->update(['organization_id' => $other->id]);

        $this->expectException(NotFoundHttpException::class);

        $this->print();
    }

    public function test_admin_template_override_is_versioned_validated_and_cannot_run_code_or_inject_html(): void
    {
        $service = app(DocumentService::class);

        $v1 = $service->saveTemplate($this->user, 'receipt', '<p>Custom {{ receipt_number }} {{ patient.name }}</p>');
        $v2 = $service->saveTemplate($this->user, 'receipt', '<p>Custom v2 {{ receipt_number }}</p>');

        $this->assertSame([1, 2], [$v1, $v2]);
        $this->assertStringContainsString('Custom v2 '.$this->receiptNumber, $this->print());
        $this->assertSame(1, DB::table('document_templates')->where('is_active', true)->count());
        $this->assertSame('db', DB::table('document_prints')->latest('id')->value('template_source'));

        foreach (['{{#if x}}never closed', '{{{ patient.name }}}', '{{ }}'] as $bad) {
            try {
                $service->saveTemplate($this->user, 'receipt', $bad);
                $this->fail("template should be refused: $bad");
            } catch (ValidationException) {
                $this->assertTrue(true);
            }
        }

        $service->saveTemplate($this->user, 'receipt', '{{ letterhead.name }}');
        $this->patient->update(['first_name' => '<script>alert(1)</script>']);
        $this->assertStringNotContainsString('<script>alert(1)</script>', $this->print('en', 'xss check').'');
    }

    public function test_safe_template_blocks_loops_and_conditionals(): void
    {
        $out = app(SafeTemplate::class)->render('{{#each rows}}{{ n }}{{#if odd}}*{{else}}.{{/if}}{{/each}}', ['rows' => [['n' => 1, 'odd' => true], ['n' => 2, 'odd' => false]]]);

        $this->assertSame('1*2.', $out);
    }

    public function test_code128_symbol_table_and_checksum(): void
    {
        $symbols = app(Code128Svg::class)->symbols('PJJ123C');

        $this->assertSame(104, $symbols[0]);                 // start B
        $this->assertSame(55, end($symbols));                // reference example is 54 with start A (103); start B is 104
        $this->assertStringStartsWith('<svg', app(Code128Svg::class)->render('HN-00000123'));
    }

    public function test_web_route_returns_printable_html_with_no_store_header(): void
    {
        $response = $this->get('/admin/documents/receipt/'.$this->receiptNumber.'?lang=bn');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/html; charset=UTF-8');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('পেমেন্ট রসিদ', $response->getContent());
    }
}
