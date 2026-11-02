<?php

namespace App\Services\Facility;

use App\Models\Facility\FacCleaningAudit;
use App\Models\Facility\FacCleaningTask;
use App\Models\Ipd\IpdBed;
use App\Models\Ipd\IpdBedAllocation;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Ipd\IpdBedStatusService;
use App\Services\ModuleManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/**
 * Cleaning. A bed that was released and is waiting to be cleaned gets one task (made from IPD's event, never by editing IPD); the cleaner works through the checklist; after an infection or
 * isolation a second person inspects; then IPD's own service makes the bed available. Turnaround is kept. Audits score an area against a checklist.
 */
class HousekeepingService
{
    public function __construct(private readonly FacilityPolicy $policy, private readonly FacilityAlerts $alerts, private readonly IpdBedStatusService $beds, private readonly ModuleManager $modules, private readonly AuditLogger $audit) {}

    /** The listener's entry point. A bed is cleaned once per stay; a hospital that has not switched Facility on gets nothing. */
    public function onBedReleased(IpdBedAllocation $allocation): ?FacCleaningTask
    {
        $org = (int) $allocation->organization_id;
        if (! $this->modules->isEnabled('facility', $org)) {
            return null;
        }
        $bed = IpdBed::query()->find($allocation->bed_id);
        if (! $bed || $bed->status !== IpdBed::STATUS_CLEANING) {
            return null;
        }
        if (FacCleaningTask::query()->where(['organization_id' => $org, 'source_type' => 'bed_allocation', 'source_id' => $allocation->id])->exists()) {
            return null;
        }
        $isolated = Schema::hasTable('qa_isolations') && DB::table('qa_isolations')->where('patient_id', $allocation->patient_id)->where('started_at', '>=', $allocation->allocated_at)->exists();
        $kind = $isolated ? 'isolation_terminal' : 'bed_turnover';

        return FacCleaningTask::create(['organization_id' => $org, 'branch_id' => $bed->branch_id, 'kind' => $kind, 'bed_id' => $bed->id, 'location' => __('Bed :b', ['b' => $bed->bed_code]), 'source_type' => 'bed_allocation', 'source_id' => $allocation->id,
            'priority' => $isolated ? 'urgent' : 'normal', 'status' => 'pending', 'requested_at' => now(), 'checklist' => $this->checklist($kind)]);
    }

    /** A task someone asks for by hand (a room, a theatre). */
    public function request(int $organizationId, string $kind, string $location, User $by, ?int $branchId = null): FacCleaningTask
    {
        if (! array_key_exists($kind, (array) $this->policy->get('housekeeping.checklists', []))) {
            throw ValidationException::withMessages(['kind' => __('Choose what is to be cleaned.')]);
        }
        if (mb_strlen(trim($location)) < 2) {
            throw ValidationException::withMessages(['location' => __('Say where.')]);
        }

        return FacCleaningTask::create(['organization_id' => $organizationId, 'branch_id' => $branchId, 'kind' => $kind, 'location' => mb_substr(trim($location), 0, 120), 'priority' => 'normal', 'status' => 'pending', 'requested_at' => now(), 'checklist' => $this->checklist($kind)]);
    }

    public function start(FacCleaningTask $task, User $by): FacCleaningTask
    {
        $this->mustBe($task, ['pending']);
        $task->update(['status' => 'in_progress', 'started_at' => now(), 'assigned_to' => $task->assigned_to ?? $by->id]);

        return $task;
    }

    /** @param list<int> $done positions in the checklist that were done */
    public function complete(FacCleaningTask $task, User $by, array $done, ?string $note = null): FacCleaningTask
    {
        $this->mustBe($task, ['pending', 'in_progress']);
        $list = (array) $task->checklist;
        foreach ($list as $i => $row) {
            $list[$i]['done'] = in_array($i, array_map('intval', $done), true);
        }
        $left = collect($list)->where('done', false)->count();
        if ($left > 0) {
            throw ValidationException::withMessages(['checklist' => __(':n step(s) of the checklist are not done.', ['n' => $left])]);
        }
        $inspect = in_array($task->kind, $this->policy->list('housekeeping.inspect_kinds'), true);

        return DB::transaction(function () use ($task, $by, $list, $note, $inspect) {
            $task->update(['status' => 'done', 'started_at' => $task->started_at ?? now(), 'done_at' => now(), 'done_by' => $by->id, 'checklist' => $list, 'note' => $note ? mb_substr($note, 0, 500) : null]);
            if (! $inspect) {
                $this->finish($task, $by);
            }
            $this->audit->log('UPDATE', FacCleaningTask::class, $task->id, null, ['status' => 'done']);

            return $task->refresh();
        });
    }

    /** After an infection or isolation someone else looks and signs. */
    public function inspect(FacCleaningTask $task, User $by, bool $passed, ?string $note = null): FacCleaningTask
    {
        $this->mustBe($task, ['done']);
        if (! in_array($task->kind, $this->policy->list('housekeeping.inspect_kinds'), true)) {
            throw ValidationException::withMessages(['status' => __('This clean needs no inspection.')]);
        }
        if ($task->done_by === $by->id) {
            throw ValidationException::withMessages(['status' => __('A second person must inspect the clean.')]);
        }
        if (! $passed) {
            if (mb_strlen(trim((string) $note)) < 5) {
                throw ValidationException::withMessages(['note' => __('Say what must be redone.')]);
            }
            $task->update(['status' => 'pending', 'done_at' => null, 'done_by' => null, 'note' => mb_substr((string) $note, 0, 500)]);

            return $task;
        }

        return DB::transaction(function () use ($task, $by, $note) {
            $task->update(['status' => 'inspected', 'inspected_by' => $by->id, 'inspected_at' => now(), 'note' => $note ? mb_substr($note, 0, 500) : $task->note]);
            $this->finish($task, $by);
            $this->audit->log('UPDATE', FacCleaningTask::class, $task->id, null, ['status' => 'inspected']);

            return $task->refresh();
        });
    }

    public function cancel(FacCleaningTask $task, string $reason): FacCleaningTask
    {
        $this->mustBe($task, ['pending', 'in_progress']);
        if (mb_strlen(trim($reason)) < 3) {
            throw ValidationException::withMessages(['reason' => __('Say why.')]);
        }
        $task->update(['status' => 'cancelled', 'note' => mb_substr($reason, 0, 500)]);

        return $task;
    }

    /** Score an area against a checklist. @param list<array{item:string,ok:bool}> $results */
    public function audit(int $organizationId, string $area, array $results, User $by, ?string $date = null, ?int $branchId = null): FacCleaningAudit
    {
        if (mb_strlen(trim($area)) < 2 || $results === []) {
            throw ValidationException::withMessages(['area' => __('Name the area and check at least one item.')]);
        }
        $checked = count($results);
        $passed = collect($results)->where('ok', true)->count();
        $score = round(100 * $passed / $checked, 2);
        $audit = FacCleaningAudit::create(['organization_id' => $organizationId, 'branch_id' => $branchId, 'area' => mb_substr(trim($area), 0, 120), 'audited_on' => $date ?: today()->toDateString(), 'items_checked' => $checked, 'items_passed' => $passed, 'score' => $score,
            'findings' => collect($results)->where('ok', false)->pluck('item')->values()->all(), 'auditor_id' => $by->id]);
        if ($score < $this->policy->int('housekeeping.audit_pass_percent', 85)) {
            $this->alerts->raise($organizationId, 'warning', __('Cleaning audit of :a scored :s%.', ['a' => $area, 's' => $score]), FacCleaningAudit::class, $audit->id, 'fac-audit-'.$audit->id, 'facility.housekeeping.manage', false, $branchId);
        }

        return $audit;
    }

    /** @return list<array{item:string,done:bool}> */
    private function checklist(string $kind): array
    {
        return collect((array) ($this->policy->get('housekeeping.checklists', [])[$kind] ?? []))->map(fn ($i) => ['item' => (string) $i, 'done' => false])->values()->all();
    }

    private function finish(FacCleaningTask $task, User $by): void
    {
        $end = $task->inspected_at ?? $task->done_at ?? now();
        $released = false;
        if ($task->bed_id) {
            $bed = IpdBed::query()->find($task->bed_id);
            if ($bed && $bed->status === IpdBed::STATUS_CLEANING) {
                $this->beds->transitionTo($bed, IpdBed::STATUS_AVAILABLE, $by, __('Cleaned'));
                $released = true;
            }
        }
        $task->update(['bed_released' => $released, 'turnaround_minutes' => (int) max(0, $task->requested_at->diffInMinutes($end))]);
    }

    /** @param list<string> $states */
    private function mustBe(FacCleaningTask $task, array $states): void
    {
        if (! in_array($task->status, $states, true)) {
            throw ValidationException::withMessages(['status' => __('This task is :s.', ['s' => str_replace('_', ' ', $task->status)])]);
        }
    }
}
