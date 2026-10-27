<?php

namespace App\Services\Inventory;

use App\Models\Inventory\ProcurementApproval;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Approval by amount (S04, S06): one row per level the amount needs, decided in order, every level by a different person, and never
 * by the person who raised the document.
 */
class ApprovalService
{
    public function __construct(private readonly InventoryPolicy $policy) {}

    /** Opens the chain for a document of this total (replaces an earlier, unfinished one). */
    public function begin(int $organizationId, string $type, int $documentId, string $amount): void
    {
        DB::transaction(function () use ($organizationId, $type, $documentId, $amount) {
            ProcurementApproval::query()->where(['document_type' => $type, 'document_id' => $documentId])->where('status', 'pending')->delete();
            foreach ($this->policy->approvalLevels($amount) as $level) {
                ProcurementApproval::query()->firstOrCreate(
                    ['document_type' => $type, 'document_id' => $documentId, 'level' => $level['level']],
                    ['organization_id' => $organizationId, 'permission' => $level['permission'], 'status' => 'pending'],
                );
            }
        });
    }

    /** Levels a changed total needs on top of those already approved. */
    public function extend(int $organizationId, string $type, int $documentId, string $amount): bool
    {
        $added = false;
        foreach ($this->policy->approvalLevels($amount) as $level) {
            $row = ProcurementApproval::query()->firstOrCreate(
                ['document_type' => $type, 'document_id' => $documentId, 'level' => $level['level']],
                ['organization_id' => $organizationId, 'permission' => $level['permission'], 'status' => 'pending'],
            );
            $added = $added || $row->wasRecentlyCreated;
        }

        return $added;
    }

    /** @return bool true when this decision completed the chain */
    public function approve(string $type, int $documentId, User $by, ?int $raisedBy, ?string $comment = null): bool
    {
        return DB::transaction(function () use ($type, $documentId, $by, $raisedBy, $comment) {
            $next = ProcurementApproval::query()->where(['document_type' => $type, 'document_id' => $documentId, 'status' => 'pending'])->orderBy('level')->lockForUpdate()->first();
            if (! $next) {
                throw ValidationException::withMessages(['approval' => 'Nothing is waiting for approval.']);
            }
            if ($raisedBy !== null && $by->id === $raisedBy) {
                throw ValidationException::withMessages(['approval' => 'You cannot approve a document you raised.']);
            }
            if (! $by->can($next->permission)) {
                throw ValidationException::withMessages(['approval' => 'The next approval needs '.$this->label($next->level).'.']);
            }
            if (ProcurementApproval::query()->where(['document_type' => $type, 'document_id' => $documentId, 'status' => 'approved', 'decided_by' => $by->id])->exists()) {
                throw ValidationException::withMessages(['approval' => 'Each level must be approved by a different person.']);
            }
            $next->update(['status' => 'approved', 'decided_by' => $by->id, 'decided_at' => now(), 'comment' => $comment]);

            return ! ProcurementApproval::query()->where(['document_type' => $type, 'document_id' => $documentId, 'status' => 'pending'])->exists();
        });
    }

    public function reject(string $type, int $documentId, User $by, string $comment): void
    {
        $next = ProcurementApproval::query()->where(['document_type' => $type, 'document_id' => $documentId, 'status' => 'pending'])->orderBy('level')->first();
        if (! $next || ! $by->can($next->permission)) {
            throw ValidationException::withMessages(['approval' => 'You cannot decide this approval.']);
        }
        $next->update(['status' => 'rejected', 'decided_by' => $by->id, 'decided_at' => now(), 'comment' => $comment]);
    }

    public function isComplete(string $type, int $documentId): bool
    {
        $rows = ProcurementApproval::query()->where(['document_type' => $type, 'document_id' => $documentId])->get();

        return $rows->isNotEmpty() && $rows->every(fn ($r) => $r->status === 'approved');
    }

    /** @return Collection<int, ProcurementApproval> */
    public function chain(string $type, int $documentId)
    {
        return ProcurementApproval::query()->where(['document_type' => $type, 'document_id' => $documentId])->orderBy('level')->get();
    }

    public function label(int $level): string
    {
        return (string) collect((array) $this->policy->get('approval_levels'))->firstWhere('level', $level)['label'];
    }
}
