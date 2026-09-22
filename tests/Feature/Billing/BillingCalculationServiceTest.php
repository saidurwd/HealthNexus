<?php

namespace Tests\Feature\Billing;

use App\Services\Billing\BillingCalculationService;
use Tests\TestCase;

class BillingCalculationServiceTest extends TestCase
{
    private BillingCalculationService $calculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calculator = new BillingCalculationService;
    }

    public function test_calculates_line_with_percentage_discount_and_exclusive_tax(): void
    {
        $line = $this->calculator->calculateLine('1000.00', 2, 'percentage', '10', '15', false);

        // gross = 2000, discount = 10% of 2000 = 200, taxable = 1800, tax = 15% of 1800 = 270, net = 2070
        $this->assertSame('2000.00', $line['gross_amount']);
        $this->assertSame('200.00', $line['discount_amount']);
        $this->assertSame('270.00', $line['tax_amount']);
        $this->assertSame('2070.00', $line['net_amount']);
    }

    public function test_calculates_line_with_fixed_discount_capped_at_gross(): void
    {
        $line = $this->calculator->calculateLine('50.00', 1, 'fixed', '999.00', '0', false);

        $this->assertSame('50.00', $line['gross_amount']);
        $this->assertSame('50.00', $line['discount_amount']);
        $this->assertSame('0.00', $line['net_amount']);
    }

    public function test_calculates_line_with_tax_inclusive_pricing(): void
    {
        // unit price already includes 10% tax: 110 gross, tax-inclusive means net stays 110,
        // tax is backed out: pre_tax = 110 / 1.10 = 100.00, tax = 10.00
        $line = $this->calculator->calculateLine('110.00', 1, 'none', '0', '10', true);

        $this->assertSame('110.00', $line['gross_amount']);
        $this->assertSame('10.00', $line['tax_amount']);
        $this->assertSame('110.00', $line['net_amount']);
    }

    public function test_invoice_totals_aggregate_lines_and_round_nearest(): void
    {
        $lines = [
            ['gross_amount' => '100.00', 'discount_amount' => '0.00', 'tax_amount' => '5.00', 'net_amount' => '105.00'],
            ['gross_amount' => '50.00', 'discount_amount' => '5.00', 'tax_amount' => '2.25', 'net_amount' => '47.25'],
        ];

        $totals = $this->calculator->calculateInvoiceTotals($lines, 'none', '0', 'nearest', 2);

        $this->assertSame('150.00', $totals['subtotal']);
        $this->assertSame('5.00', $totals['discount_amount']);
        $this->assertSame('7.25', $totals['tax_amount']);
        $this->assertSame('152.25', $totals['grand_total']);
    }

    public function test_invoice_level_discount_applies_on_top_of_line_totals(): void
    {
        $lines = [
            ['gross_amount' => '1000.00', 'discount_amount' => '0.00', 'tax_amount' => '0.00', 'net_amount' => '1000.00'],
        ];

        $totals = $this->calculator->calculateInvoiceTotals($lines, 'percentage', '10', 'nearest', 2);

        // 10% of (1000 - 0) = 100 additional discount
        $this->assertSame('100.00', $totals['discount_amount']);
        $this->assertSame('900.00', $totals['grand_total']);
    }

    public function test_rounding_modes_behave_as_expected(): void
    {
        $this->assertSame('10.13', $this->calculator->applyRounding('10.125', 'up', 2));
        $this->assertSame('10.12', $this->calculator->applyRounding('10.129', 'down', 2));
        $this->assertSame('10.13', $this->calculator->applyRounding('10.125', 'nearest', 2));
    }
}
