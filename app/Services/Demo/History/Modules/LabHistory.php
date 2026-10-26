<?php

namespace App\Services\Demo\History\Modules;

use App\Models\Laboratory\LabOrder;
use App\Models\Laboratory\LabOrderItem;
use App\Services\Demo\History\DemoHistoryModule;
use App\Services\Demo\History\HistoryContext;
use App\Services\Laboratory\LabReferenceRangeResolver;
use App\Services\Laboratory\LabReportService;
use App\Services\Laboratory\ResultEntryService;
use App\Services\Laboratory\ResultValidationService;
use App\Services\Laboratory\SpecimenService;

/** Moves the lab orders the OPD history created through collection, receipt, result entry, validation and the final report. */
class LabHistory implements DemoHistoryModule
{
    public function __construct(
        private readonly SpecimenService $specimens,
        private readonly ResultEntryService $results,
        private readonly ResultValidationService $validation,
        private readonly LabReportService $reports,
        private readonly LabReferenceRangeResolver $ranges,
    ) {}

    public function name(): string
    {
        return 'Laboratory';
    }

    public function module(): string
    {
        return 'lab';
    }

    public function run(HistoryContext $context): string
    {
        $tech = $context->staff('lab_technician');
        $validators = $context->staff('senior_lab_technician')->concat($context->staff('lab_technician'))->unique('id')->values();
        $pathologist = $context->staff('pathologist')->first();
        $counts = ['orders' => 0, 'reported' => 0, 'rejected' => 0, 'in_progress' => 0, 'abnormal' => 0];
        $recent = $context->to->subDay()->startOfDay();

        LabOrder::query()->where('organization_id', $context->organization->id)->orderBy('id')->each(function (LabOrder $order) use ($context, $tech, $validators, $pathologist, $recent, &$counts) {
            $counts['orders']++;
            $start = $order->ordered_at->toImmutable();
            $analyst = $context->pick($tech);
            $reviewer = $validators->firstWhere(fn ($u) => $u->id !== $analyst->id) ?? $analyst;

            // The newest orders stay unfinished, as on a real day: some waiting for collection, some collected, some resulted.
            $stage = $start->gte($recent) ? mt_rand(0, 3) : 9;
            if ($stage === 0) {
                $counts['in_progress']++;

                return;
            }

            $specimen = $context->at($start->addMinutes(20), fn () => $this->specimens->collect($order->refresh(), ['order_item_ids' => $order->items()->where('status', 'pending')->pluck('id')->all()], $analyst));
            if ($stage === 1) {
                $counts['in_progress']++;

                return;
            }
            if ($stage === 9 && $context->chance(3)) {
                $context->at($start->addMinutes(35), fn () => $this->specimens->reject($specimen, array_key_first(config('laboratory.rejection_reasons')), $analyst));
                $counts['rejected']++;

                return;
            }
            $context->at($start->addMinutes(45), fn () => $this->specimens->receive($specimen->refresh(), $analyst));
            if ($stage === 2) {
                $counts['in_progress']++;

                return;
            }

            $entered = [];
            foreach ($order->items()->where('status', 'received')->with('test')->get() as $item) {
                $result = $context->at($start->addHours(2), fn () => $this->results->enter($item->refresh(), $this->value($item, $context), $analyst));
                $result->abnormal_flag !== 'normal' && $result->abnormal_flag !== 'not_applicable' && $counts['abnormal']++;
                $entered[] = $result;
            }
            if ($stage === 3) {
                $counts['in_progress']++;

                return;
            }
            // Validation only opens once every requested test has a result (the order is then awaiting validation).
            foreach ($entered as $result) {
                $result = $context->at($start->addHours(3), fn () => $this->validation->technicalValidate($result->refresh(), $reviewer));
                if ($result->result_status === 'technically_validated') {
                    $context->at($start->addHours(4), fn () => $this->validation->pathologistApprove($result, $pathologist));
                }
            }
            if ($order->refresh()->status === 'validated') {
                $context->at($start->addHours(5), fn () => $this->reports->finalize($order, $reviewer));
                $counts['reported']++;
            }
        });

        return sprintf('%d orders: %d reported, %d in progress, %d specimens rejected, %d abnormal results', $counts['orders'], $counts['reported'], $counts['in_progress'], $counts['rejected'], $counts['abnormal']);
    }

    /** @return array<string, string> a plausible value for the test: mostly inside its reference range, sometimes outside */
    private function value(LabOrderItem $item, HistoryContext $context): array
    {
        $test = $item->test;
        if (in_array($test->test_type, ['quantitative', 'semi_quantitative', 'calculated'], true)) {
            $range = $this->ranges->resolve($test, $item->labOrder->patient);
            $low = (float) ($range['low'] ?? 1);
            $high = (float) ($range['high'] ?? max($low + 1, 100));
            $span = max($high - $low, 0.1);
            $value = $context->chance(75) ? $low + $span * mt_rand(10, 90) / 100 : ($context->chance(50) ? $low - $span * mt_rand(5, 30) / 100 : $high + $span * mt_rand(5, 30) / 100);

            return ['numeric_value' => (string) round(max($value, 0.1), 2)];
        }
        if ($test->test_type === 'qualitative') {
            return ['qualitative_value' => $context->chance(85) ? 'negative' : 'positive'];
        }

        return ['text_value' => 'Within normal limits'];
    }
}
