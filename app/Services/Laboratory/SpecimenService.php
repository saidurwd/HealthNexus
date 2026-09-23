<?php

namespace App\Services\Laboratory;

use App\Events\Laboratory\SampleRejected;
use App\Models\Laboratory\LabOrder;
use App\Models\Laboratory\LabSpecimen;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SpecimenService
{
    public function __construct(
        private readonly LabNumberGenerator $numbers,
        private readonly LabOrderLifecycleService $lifecycle,
    ) {}

    /**
     * @param  array{specimen_type_id?:int|null,container_type_id?:int|null,storage_location?:string|null,notes?:string|null,order_item_ids?:int[]}  $data
     */
    public function collect(LabOrder $order, array $data, User $collector): LabSpecimen
    {
        return DB::transaction(function () use ($order, $data, $collector) {
            $specimen = LabSpecimen::create([
                'company_id' => $order->company_id,
                'branch_id' => $order->branch_id,
                'lab_order_id' => $order->id,
                'specimen_type_id' => $data['specimen_type_id'] ?? null,
                'container_type_id' => $data['container_type_id'] ?? null,
                'accession_number' => $this->numbers->generateAccessionNumber($order->company_id, $order->branch_id),
                'status' => 'collected',
                'collected_by' => $collector->id,
                'collected_at' => now(),
                'storage_location' => $data['storage_location'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $specimen->update(['barcode' => $specimen->accession_number]);

            $itemIds = $data['order_item_ids'] ?? $order->items()->where('status', 'pending')->pluck('id')->all();

            $order->items()->whereIn('id', $itemIds)->update([
                'specimen_id' => $specimen->id,
                'status' => 'collected',
                'collected_at' => now(),
            ]);

            // 'registered' can't jump straight to 'collected' in the lifecycle's transition
            // graph — it must pass through 'awaiting_collection' first.
            if ($order->status === 'registered') {
                $order = $this->lifecycle->transitionTo($order, 'awaiting_collection');
            }

            $this->lifecycle->transitionTo($order, 'collected');

            return $specimen->refresh();
        });
    }

    public function receive(LabSpecimen $specimen, User $receiver): LabSpecimen
    {
        return DB::transaction(function () use ($specimen, $receiver) {
            if ($specimen->status !== 'collected') {
                throw ValidationException::withMessages(['specimen' => "Cannot receive a specimen in '{$specimen->status}' status."]);
            }

            $specimen->update([
                'status' => 'received',
                'received_by' => $receiver->id,
                'received_at' => now(),
            ]);

            $specimen->orderItems()->update(['status' => 'received']);

            $this->lifecycle->transitionTo($specimen->labOrder, 'received');

            return $specimen->refresh();
        });
    }

    public function reject(LabSpecimen $specimen, string $reason, User $rejector, ?string $notes = null): LabSpecimen
    {
        return DB::transaction(function () use ($specimen, $reason, $rejector, $notes) {
            if (! array_key_exists($reason, config('laboratory.rejection_reasons'))) {
                throw ValidationException::withMessages(['reason' => 'Invalid rejection reason.']);
            }

            $specimen->update([
                'status' => 'rejected',
                'rejected_by' => $rejector->id,
                'rejected_at' => now(),
                'rejection_reason' => $reason,
                'notes' => $notes ?? $specimen->notes,
            ]);

            // Recollection is required — the affected order items go back to 'pending' and the
            // order reverts to awaiting_collection rather than being terminally rejected.
            $specimen->orderItems()->update(['status' => 'pending', 'specimen_id' => null]);

            $order = $specimen->labOrder;

            if (in_array($order->status, ['collected', 'received'], true)) {
                $this->lifecycle->transitionTo($order, 'awaiting_collection');
            }

            event(new SampleRejected($specimen));

            return $specimen->refresh();
        });
    }
}
