<?php

namespace App\Services\Demo\History\Modules;

use App\Models\Pharmacy\PharmacyDispensing;
use App\Models\Pharmacy\PharmacyOrder;
use App\Models\Pharmacy\PharmacyStore;
use App\Services\Demo\History\DemoHistoryModule;
use App\Services\Demo\History\HistoryContext;
use App\Services\Pharmacy\PharmacyDispensingService;
use App\Services\Pharmacy\PharmacyReturnService;
use Illuminate\Validation\ValidationException;

/** Dispenses the prescriptions OPD issued — first-in-first-out from the monthly stock, some only partly — verifies them, and takes a few returns. */
class PharmacyHistory implements DemoHistoryModule
{
    public function __construct(private readonly PharmacyDispensingService $dispensing, private readonly PharmacyReturnService $returns) {}

    public function name(): string
    {
        return 'Pharmacy';
    }

    public function module(): string
    {
        return 'pharmacy';
    }

    public function run(HistoryContext $context): string
    {
        $store = PharmacyStore::where('organization_id', $context->organization->id)->orderBy('id')->firstOrFail();
        $pharmacists = $context->staff('pharmacist');
        $recent = $context->to->subDay()->startOfDay();
        $counts = ['orders' => 0, 'full' => 0, 'partial' => 0, 'waiting' => 0, 'blocked' => 0, 'returns' => 0];

        PharmacyOrder::query()->where('organization_id', $context->organization->id)->with('items')->orderBy('id')->each(function (PharmacyOrder $order) use ($context, $store, $pharmacists, $recent, &$counts) {
            $counts['orders']++;
            $start = $order->ordered_at->toImmutable();
            if (($start->gte($recent) && $context->chance(50)) || $context->chance(4)) {
                $counts['waiting']++;

                return;
            }

            $lines = $order->items->where('status', 'pending')->map(function ($item) use ($context) {
                $wanted = $item->remainingQuantity() ?? 10;

                return ['order_item_id' => $item->id, 'quantity' => max(1, $context->chance(15) ? intdiv($wanted, 2) : $wanted)];
            })->values()->all();
            if ($lines === []) {
                $counts['blocked']++;

                return;
            }

            $pharmacist = $context->pick($pharmacists);
            try {
                $done = $context->at($start->addMinutes(30), fn () => $this->dispensing->dispense($order->refresh(), $store, $lines, $pharmacist));
            } catch (ValidationException) {
                $counts['blocked']++;

                return;
            }
            $verifier = $pharmacists->firstWhere(fn ($u) => $u->id !== $pharmacist->id) ?? $pharmacist;
            $context->at($start->addMinutes(40), fn () => $this->dispensing->verify($done, $verifier));
            $done->status === PharmacyDispensing::STATUS_FULLY_DISPENSED ? $counts['full']++ : $counts['partial']++;

            if ($context->chance(3) && $done->items->isNotEmpty()) {
                $item = $done->items->first();
                $return = $context->at($start->addDay(), fn () => $this->returns->request($done, [['dispensing_item_id' => $item->id, 'quantity_returned' => 1]], 'Patient did not need it', $pharmacist));
                $context->at($start->addDay()->addHour(), fn () => $this->returns->verify($return, [['return_item_id' => $return->items->first()->id, 'returnable_to_stock' => true]], $verifier));
                $counts['returns']++;
            }
        });

        return sprintf('%d orders: %d fully and %d partly dispensed, %d waiting, %d blocked by a safety check or no stock, %d returns', $counts['orders'], $counts['full'], $counts['partial'], $counts['waiting'], $counts['blocked'], $counts['returns']);
    }
}
