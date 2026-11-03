<?php

namespace App\Services\Ai;

use App\Models\Ai\AiIncident;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Clinical\Alerts\AlertRequest;
use App\Services\Clinical\Alerts\AlertService;
use Illuminate\Validation\ValidationException;
use Throwable;

/** Things that went wrong or nearly did: opened by the system (an action line removed, instruction-like text, a wrong-patient try, drift) or by a person; worked with an owner and closed with a resolution. */
class AiIncidents
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function open(int $organizationId, string $kind, string $severity, ?string $useCase, ?int $outputId, string $description, ?User $by = null): AiIncident
    {
        if (! in_array($kind, ['wrong_patient', 'unsafe_output', 'data_leak', 'injection', 'drift', 'other'], true) || ! in_array($severity, ['low', 'medium', 'high'], true)) {
            throw ValidationException::withMessages(['kind' => __('Choose the kind and the seriousness.')]);
        }
        $incident = AiIncident::create(['organization_id' => $organizationId, 'use_case_code' => $useCase, 'output_id' => $outputId, 'severity' => $severity, 'kind' => $kind, 'description' => mb_substr($description, 0, 2000), 'status' => 'open', 'reported_by' => $by?->id]);
        if ($severity === 'high') {
            try {
                $recipients = User::query()->permission('ai.incident.manage')->where('is_active', true)->whereHas('organizations', fn ($q) => $q->where('organizations.id', $organizationId))->limit(20)->pluck('id')->all();
                app(AlertService::class)->raise(new AlertRequest(organizationId: $organizationId, category: 'ai', severity: 'critical', message: __('AI incident: :d', ['d' => mb_substr($description, 0, 140)]), sourceModule: 'ai',
                    sourceType: AiIncident::class, sourceId: $incident->id, recipientUserIds: $recipients, ackRequired: true, dedupKey: 'ai-incident-'.$incident->id));
            } catch (Throwable) {
                // an alert must never undo the record
            }
        }

        return $incident;
    }

    public function assign(AiIncident $i, User $owner): AiIncident
    {
        $i->update(['owner_id' => $owner->id, 'status' => $i->status === 'open' ? 'investigating' : $i->status]);

        return $i;
    }

    public function close(AiIncident $i, User $by, string $resolution): AiIncident
    {
        if ($i->status === 'closed') {
            throw ValidationException::withMessages(['status' => __('Already closed.')]);
        }
        if (mb_strlen(trim($resolution)) < 10) {
            throw ValidationException::withMessages(['resolution' => __('Say what was found and what was done.')]);
        }
        $i->update(['status' => 'closed', 'resolution' => mb_substr($resolution, 0, 500), 'closed_at' => now()]);
        $this->audit->log('UPDATE', AiIncident::class, $i->id, null, ['status' => 'closed']);

        return $i;
    }
}
