<?php

namespace App\Services\Billing;

/**
 * Pure, decimal-safe (bcmath) money math. No I/O, no Eloquent — every amount in and out is a
 * numeric string. Never use floats for financial arithmetic here.
 */
class BillingCalculationService
{
    private const SCALE = 2;

    /**
     * Gross -> discount -> taxable -> tax -> net for a single line.
     *
     * @return array{gross_amount:string,discount_amount:string,tax_amount:string,net_amount:string}
     */
    public function calculateLine(
        string $unitPrice,
        int $quantity,
        string $discountType,
        string $discountValue,
        string $taxRate,
        bool $taxInclusive = false,
    ): array {
        $gross = bcmul($unitPrice, (string) $quantity, self::SCALE);

        $discount = match ($discountType) {
            'percentage' => bcdiv(bcmul($gross, $discountValue, 6), '100', self::SCALE),
            'fixed' => bccomp($discountValue, $gross, self::SCALE) === 1 ? $gross : $discountValue,
            default => '0.00',
        };

        $taxable = bcsub($gross, $discount, self::SCALE);

        if ($taxInclusive) {
            // $taxable already contains the tax; back it out rather than adding on top.
            $divisor = bcadd('1', bcdiv($taxRate, '100', 6), 6);
            $preTax = bcdiv($taxable, $divisor, self::SCALE);
            $tax = bcsub($taxable, $preTax, self::SCALE);
            $net = $taxable;
        } else {
            $tax = bcdiv(bcmul($taxable, $taxRate, 6), '100', self::SCALE);
            $net = bcadd($taxable, $tax, self::SCALE);
        }

        return [
            'gross_amount' => $gross,
            'discount_amount' => $discount,
            'tax_amount' => $tax,
            'net_amount' => $net,
        ];
    }

    /**
     * Aggregates already-calculated lines, applies an invoice-level discount on top of the
     * line-level net, then rounds per the configured policy.
     *
     * @param  iterable<array{gross_amount:string,discount_amount:string,tax_amount:string,net_amount:string}>  $lines
     * @return array{subtotal:string,discount_amount:string,tax_amount:string,rounding_amount:string,grand_total:string}
     */
    public function calculateInvoiceTotals(
        iterable $lines,
        string $invoiceDiscountType = 'none',
        string $invoiceDiscountValue = '0',
        string $roundingMode = 'nearest',
        int $roundingPrecision = self::SCALE,
    ): array {
        $subtotal = '0.00';
        $lineDiscount = '0.00';
        $tax = '0.00';

        foreach ($lines as $line) {
            $subtotal = bcadd($subtotal, $line['gross_amount'], self::SCALE);
            $lineDiscount = bcadd($lineDiscount, $line['discount_amount'], self::SCALE);
            $tax = bcadd($tax, $line['tax_amount'], self::SCALE);
        }

        $netOfLineDiscount = bcsub($subtotal, $lineDiscount, self::SCALE);

        $invoiceDiscount = match ($invoiceDiscountType) {
            'percentage' => bcdiv(bcmul($netOfLineDiscount, $invoiceDiscountValue, 6), '100', self::SCALE),
            'fixed' => bccomp($invoiceDiscountValue, $netOfLineDiscount, self::SCALE) === 1 ? $netOfLineDiscount : $invoiceDiscountValue,
            default => '0.00',
        };

        $preRoundTotal = bcadd(bcsub($netOfLineDiscount, $invoiceDiscount, self::SCALE), $tax, self::SCALE);
        $rounded = $this->applyRounding($preRoundTotal, $roundingMode, $roundingPrecision);
        $roundingAmount = bcsub($rounded, $preRoundTotal, self::SCALE);

        return [
            'subtotal' => $subtotal,
            'discount_amount' => bcadd($lineDiscount, $invoiceDiscount, self::SCALE),
            'tax_amount' => $tax,
            'rounding_amount' => $roundingAmount,
            'grand_total' => $rounded,
        ];
    }

    public function applyRounding(string $amount, string $mode, int $precision): string
    {
        return match ($mode) {
            'up' => $this->ceil($amount, $precision),
            'down' => $this->floor($amount, $precision),
            default => $this->roundHalfUp($amount, $precision),
        };
    }

    private function floor(string $number, int $precision): string
    {
        $negative = bccomp($number, '0', 10) < 0;
        $abs = $negative ? bcmul($number, '-1', 10) : $number;
        $truncated = bcadd($abs, '0', $precision);

        if ($negative && bccomp($truncated, $abs, 10) > 0) {
            $step = bcdiv('1', bcpow('10', (string) $precision), $precision);
            $truncated = bcadd($truncated, $step, $precision);
        }

        return $negative ? bcmul($truncated, '-1', $precision) : $truncated;
    }

    private function ceil(string $number, int $precision): string
    {
        $negative = bccomp($number, '0', 10) < 0;
        $abs = $negative ? bcmul($number, '-1', 10) : $number;
        $truncated = bcadd($abs, '0', $precision);

        if (! $negative && bccomp($truncated, $abs, 10) < 0) {
            $step = bcdiv('1', bcpow('10', (string) $precision), $precision);
            $truncated = bcadd($truncated, $step, $precision);
        }

        return $negative ? bcmul($truncated, '-1', $precision) : $truncated;
    }

    private function roundHalfUp(string $number, int $precision): string
    {
        $negative = bccomp($number, '0', 10) < 0;
        $abs = $negative ? bcmul($number, '-1', 10) : $number;
        $half = bcdiv('5', bcpow('10', (string) ($precision + 1)), $precision + 10);
        $result = bcadd($abs, $half, $precision);

        return $negative ? bcmul($result, '-1', $precision) : $result;
    }
}
