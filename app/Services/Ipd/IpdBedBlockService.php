<?php

namespace App\Services\Ipd;

use App\Events\Ipd\BedBlocked;
use App\Events\Ipd\BedUnblocked;
use App\Models\Ipd\IpdBed;
use App\Models\Ipd\IpdBedBlock;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class IpdBedBlockService
{
    public function __construct(private readonly IpdBedStatusService $bedStatus) {}

    public function block(IpdBed $bed, string $reasonType, string $reason, User $user, ?\DateTimeInterface $expectedEnd = null): IpdBedBlock
    {
        return DB::transaction(function () use ($bed, $reasonType, $reason, $user, $expectedEnd) {
            $locked = IpdBed::query()->whereKey($bed->id)->lockForUpdate()->firstOrFail();

            $targetStatus = $reasonType === 'maintenance' ? IpdBed::STATUS_MAINTENANCE : IpdBed::STATUS_BLOCKED;
            $this->bedStatus->transitionTo($locked, $targetStatus, $user, $reason);

            $block = IpdBedBlock::create([
                'company_id' => $locked->company_id,
                'branch_id' => $locked->branch_id,
                'bed_id' => $locked->id,
                'reason_type' => $reasonType,
                'reason' => $reason,
                'start_at' => now(),
                'expected_end_at' => $expectedEnd,
                'status' => IpdBedBlock::STATUS_ACTIVE,
                'requested_by' => $user->id,
                'approved_by' => $user->id,
            ]);

            event(new BedBlocked($block));

            return $block;
        });
    }

    public function unblock(IpdBedBlock $block, User $user): IpdBedBlock
    {
        return DB::transaction(function () use ($block, $user) {
            if ($block->status !== IpdBedBlock::STATUS_ACTIVE) {
                throw ValidationException::withMessages(['block' => 'This block has already ended.']);
            }

            $bed = IpdBed::query()->whereKey($block->bed_id)->lockForUpdate()->firstOrFail();
            $this->bedStatus->transitionTo($bed, IpdBed::STATUS_AVAILABLE, $user);

            $block->update(['status' => IpdBedBlock::STATUS_ENDED, 'actual_end_at' => now()]);

            event(new BedUnblocked($block));

            return $block->refresh();
        });
    }
}
