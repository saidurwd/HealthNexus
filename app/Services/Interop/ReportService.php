<?php

namespace App\Services\Interop;

use App\Models\Integration\DicomWorklistEntry;
use App\Models\Integration\Hl7InboundResult;
use App\Models\Integration\IntegrationAccessLog;
use App\Models\Integration\IntegrationMessage;
use App\Models\Integration\IntegrationPartner;
use Illuminate\Support\Facades\DB;

/** Numbers for the integration screens. Counts from the owning tables; nothing is stored. */
class ReportService
{
    /** @return array<string, mixed> */
    public function dashboard(int $organizationId): array
    {
        $m = IntegrationMessage::query()->where('organization_id', $organizationId);
        $week = now()->subDays(7);
        $byStatus = (clone $m)->where('created_at', '>=', $week)->selectRaw('direction, status, count(*) c')->groupBy('direction', 'status')->get();
        $partners = IntegrationPartner::query()->where('organization_id', $organizationId)->orderBy('name')->get();
        $health = $partners->map(function (IntegrationPartner $p) use ($week) {
            $q = IntegrationMessage::query()->where('partner_id', $p->id);

            return ['partner' => $p, 'last_ok' => (clone $q)->where('status', 'processed')->max('processed_at'), 'last_failure' => (clone $q)->whereIn('status', ['retrying', 'dead'])->max('updated_at'),
                'queued' => (clone $q)->whereIn('status', ['queued', 'retrying', 'sending'])->count(), 'dead' => (clone $q)->where('status', 'dead')->count(), 'week' => (clone $q)->where('created_at', '>=', $week)->count()];
        });

        return [
            'partners' => ['active' => $partners->where('status', 'active')->count(), 'draft' => $partners->where('status', 'draft')->count(), 'paused' => $partners->where('status', 'paused')->count()],
            'out' => $byStatus->where('direction', 'out')->pluck('c', 'status')->all(), 'in' => $byStatus->where('direction', 'in')->pluck('c', 'status')->all(),
            'queue_depth' => (clone $m)->whereIn('status', ['queued', 'retrying', 'sending'])->count(),
            'oldest_due' => (clone $m)->whereIn('status', ['queued', 'retrying'])->min('next_attempt_at'),
            'dead' => (clone $m)->where('status', 'dead')->count(),
            'results_to_review' => Hl7InboundResult::query()->where(['organization_id' => $organizationId, 'status' => 'new'])->count(),
            'worklist' => DicomWorklistEntry::query()->where('organization_id', $organizationId)->selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status')->all(),
            'denied_7d' => IntegrationAccessLog::query()->where('organization_id', $organizationId)->where('created_at', '>=', $week)->where('outcome', 'denied_consent')->count(),
            'overrides_7d' => IntegrationAccessLog::query()->where('organization_id', $organizationId)->where('created_at', '>=', $week)->where('outcome', 'override')->count(),
            'fhir_reads_7d' => IntegrationAccessLog::query()->where('organization_id', $organizationId)->where('created_at', '>=', $week)->whereIn('action', ['read', 'search', 'operation'])->count(),
            'by_channel' => (clone $m)->where('created_at', '>=', $week)->selectRaw('channel, count(*) c')->groupBy('channel')->pluck('c', 'channel')->all(),
            'health' => $health,
            'avg_delivery_seconds' => DB::table('integration_messages')->where('organization_id', $organizationId)->where('direction', 'out')->where('status', 'processed')->where('created_at', '>=', $week)->get(['created_at', 'processed_at'])->avg(fn ($r) => max(0, strtotime((string) $r->processed_at) - strtotime((string) $r->created_at))),
        ];
    }
}
