<?php

namespace App\Services\Ed;

use App\Models\Ed\EdInjury;
use App\Models\Ed\EdProtocolRun;
use App\Models\Ed\EdProtocolStep;
use App\Models\Ed\EdVisit;
use App\Models\User;
use App\Services\SettingsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Stroke, heart-attack, sepsis and trauma checklists with timers, and the trauma body map. The steps are configuration, not code. */
class EdProtocolService
{
    public function __construct(private readonly SettingsService $settings) {}

    /** @return array<string, array{name: string, steps: list<array{key: string, label: string, target: ?int}>}> */
    public function protocols(): array
    {
        $custom = $this->settings->get('ed.protocols');
        $source = is_array($custom) && $custom !== [] ? $custom : config('ed_protocols');

        return array_filter($source, fn ($p) => is_array($p) && isset($p['steps']));
    }

    public function start(EdVisit $visit, User $by, string $protocol): EdProtocolRun
    {
        $definition = $this->protocols()[$protocol] ?? throw ValidationException::withMessages(['protocol' => 'Unknown protocol.']);

        return DB::transaction(function () use ($visit, $by, $protocol, $definition) {
            $locked = EdVisit::query()->lockForUpdate()->findOrFail($visit->id);
            if (! $locked->isActive()) {
                throw ValidationException::withMessages(['visit' => 'This visit is closed.']);
            }
            if (EdProtocolRun::query()->where('visit_id', $locked->id)->where('protocol', $protocol)->where('status', EdProtocolRun::ACTIVE)->exists()) {
                throw ValidationException::withMessages(['protocol' => 'This protocol is already running for the patient.']);
            }

            $started = now();
            $run = EdProtocolRun::create([
                'organization_id' => $locked->organization_id, 'visit_id' => $locked->id, 'protocol' => $protocol,
                'status' => EdProtocolRun::ACTIVE, 'started_at' => $started, 'started_by' => $by->id,
            ]);
            foreach ($definition['steps'] as $step) {
                EdProtocolStep::create([
                    'organization_id' => $locked->organization_id, 'run_id' => $run->id, 'step_key' => $step['key'], 'label' => $step['label'],
                    'due_at' => isset($step['target']) ? $started->copy()->addMinutes((int) $step['target']) : null,
                ]);
            }

            return $run->load('steps');
        });
    }

    /** Marks a step done once; the run completes when its last step is done. */
    public function completeStep(EdProtocolStep $step, User $by, ?string $note = null): EdProtocolStep
    {
        return DB::transaction(function () use ($step, $by, $note) {
            $locked = EdProtocolStep::query()->lockForUpdate()->findOrFail($step->id);
            $run = EdProtocolRun::query()->lockForUpdate()->findOrFail($locked->run_id);

            if ($run->status !== EdProtocolRun::ACTIVE) {
                throw ValidationException::withMessages(['step' => 'This protocol is no longer running.']);
            }
            if ($locked->done_at !== null) {
                return $locked;
            }

            $locked->update(['done_at' => now(), 'done_by' => $by->id, 'note' => $note]);

            if (! $run->steps()->whereNull('done_at')->exists()) {
                $run->update(['status' => EdProtocolRun::COMPLETED, 'ended_at' => now()]);
            }

            return $locked;
        });
    }

    public function cancel(EdProtocolRun $run): void
    {
        if ($run->status === EdProtocolRun::ACTIVE) {
            $run->update(['status' => EdProtocolRun::CANCELLED, 'ended_at' => now()]);
        }
    }

    /** @param array<string, mixed> $data */
    public function recordInjury(EdVisit $visit, User $by, array $data): EdInjury
    {
        if (! $visit->isActive()) {
            throw ValidationException::withMessages(['visit' => 'This visit is closed.']);
        }
        foreach (['body_region' => 'body_regions', 'injury_type' => 'injury_types', 'severity' => 'injury_severities'] as $field => $list) {
            if (! in_array($data[$field] ?? null, config("ed_protocols.$list"), true)) {
                throw ValidationException::withMessages([$field => 'Not a valid choice.']);
            }
        }

        return EdInjury::create([
            'organization_id' => $visit->organization_id, 'visit_id' => $visit->id, 'body_region' => $data['body_region'],
            'injury_type' => $data['injury_type'], 'severity' => $data['severity'], 'notes' => $data['notes'] ?? null, 'recorded_by' => $by->id,
        ]);
    }
}
