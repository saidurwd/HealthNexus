<?php

namespace App\Services\Quality;

use App\Models\Quality\QaIncident;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Brings safety events other modules already record into the one list, without copying their screens or changing their tables. Each source row becomes at most one quality incident
 * (unique on source); a repeat run adds nothing. Sources: blood-bank incidents (which already include transfusion reactions), medicine safety alerts of severe level that were overridden,
 * and WHO surgical-safety checklist phases not completed for a finished operation.
 */
class IncidentSweep
{
    public function __construct(private readonly IncidentService $incidents) {}

    /** @return array<string,int> new incidents by source */
    public function run(int $organizationId, ?int $days = null): array
    {
        $since = $days ? CarbonImmutable::now()->subDays($days) : null;

        return ['blood_incident' => $this->blood($organizationId, $since), 'pharmacy_safety_alert' => $this->pharmacy($organizationId, $since), 'ot_checklist' => $this->theatre($organizationId, $since)];
    }

    private function blood(int $org, ?CarbonImmutable $since): int
    {
        if (! Schema::hasTable('blood_incidents')) {
            return 0;
        }
        $n = 0;
        $rows = DB::table('blood_incidents')->where('organization_id', $org)->when($since, fn ($q) => $q->where('occurred_at', '>=', $since))->orderBy('id')->get();
        foreach ($rows as $r) {
            $harm = ['low' => 'minor', 'moderate' => 'moderate', 'high' => 'major', 'critical' => 'major'][$r->severity] ?? 'minor';
            $kind = $r->incident_type === 'near_miss' ? 'near_miss' : ($r->incident_type === 'transfusion_reaction' ? 'adverse_event' : 'incident');
            $n += $this->make($org, 'blood_incident', (int) $r->id, ['kind' => $kind, 'category' => 'blood', 'harm' => $harm, 'occurred_at' => $r->occurred_at, 'patient_id' => $r->patient_id,
                'description' => 'Blood bank ('.str_replace('_', ' ', $r->incident_type).'): '.$r->description, 'branch_id' => null]);
        }

        return $n;
    }

    private function pharmacy(int $org, ?CarbonImmutable $since): int
    {
        if (! Schema::hasTable('pharmacy_safety_alerts')) {
            return 0;
        }
        $n = 0;
        $rows = DB::table('pharmacy_safety_alerts')->where('organization_id', $org)->where('is_overridden', true)->where('severity', 'severe')->when($since, fn ($q) => $q->where('overridden_at', '>=', $since))->orderBy('id')->get();
        foreach ($rows as $r) {
            $n += $this->make($org, 'pharmacy_safety_alert', (int) $r->id, ['kind' => 'near_miss', 'category' => 'medication', 'harm' => 'none', 'occurred_at' => $r->overridden_at ?? $r->updated_at, 'branch_id' => $r->branch_id,
                'description' => 'A severe medicine safety warning was overridden ('.$r->alert_type.'): '.$r->explanation.($r->override_reason ? ' Reason given: '.$r->override_reason : '')]);
        }

        return $n;
    }

    private function theatre(int $org, ?CarbonImmutable $since): int
    {
        if (! Schema::hasTable('ot_surgical_safety_checklists')) {
            return 0;
        }
        $n = 0;
        $done = ['procedure_completed', 'recovery', 'transferred', 'closed'];
        $rows = DB::table('ot_surgical_safety_checklists as c')->join('ot_surgeries as s', 's.id', '=', 'c.surgery_id')->where('s.organization_id', $org)->whereIn('s.status', $done)->where('c.status', '!=', 'completed')
            ->when($since, fn ($q) => $q->where('s.completed_at', '>=', $since))->select('c.id', 'c.phase', 's.surgery_number', 's.patient_id', 's.branch_id', 's.completed_at', 's.actual_end_at')->orderBy('c.id')->get();
        foreach ($rows as $r) {
            $n += $this->make($org, 'ot_checklist', (int) $r->id, ['kind' => 'near_miss', 'category' => 'procedure', 'harm' => 'none', 'occurred_at' => $r->completed_at ?? $r->actual_end_at ?? now(), 'patient_id' => $r->patient_id, 'branch_id' => $r->branch_id,
                'description' => 'The surgical safety checklist phase "'.str_replace('_', ' ', $r->phase).'" was not completed for operation '.$r->surgery_number.'.']);
        }

        return $n;
    }

    private function make(int $org, string $type, int $id, array $d): int
    {
        if (QaIncident::query()->where(['organization_id' => $org, 'source_type' => $type, 'source_id' => $id])->exists()) {
            return 0;
        }
        $occurred = CarbonImmutable::parse((string) $d['occurred_at'], 'UTC');
        if ($occurred->isAfter(now())) {
            return 0; // not yet happened as of this run
        }
        $d['occurred_at'] = $occurred->setTimezone((string) config('quality.timezone', 'Asia/Dhaka'))->toDateTimeString();
        $d['source_type'] = $type;
        $d['source_id'] = $id;
        $d['anonymous'] = true; // nobody reported it: the system found it
        $this->incidents->report($org, $d, null);

        return 1;
    }
}
