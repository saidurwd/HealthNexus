<?php

namespace App\Services\Portal;

use App\Models\Crm\CrmCampaign;
use App\Models\Crm\CrmComplaint;
use App\Models\Crm\CrmFeedback;
use App\Models\Crm\CrmInteraction;
use App\Models\PatientPortalAccount;
use App\Models\Portal\PortalOutbox;
use App\Models\Portal\PortalPayment;
use App\Models\Portal\PortalProxy;
use App\Models\Portal\PortalRefillRequest;
use App\Models\Portal\PortalResultRelease;
use App\Models\Portal\PortalSecurityEvent;
use App\Models\Portal\PortalServiceRequest;
use App\Models\Portal\PortalThread;
use App\Services\Crm\FeedbackService;
use Illuminate\Support\Facades\DB;

/** Numbers for the desk screens. Every figure is a count from the owning tables; nothing here is stored. */
class PortalReportService
{
    public function __construct(private readonly FeedbackService $feedback) {}

    /** @return array<string, mixed> */
    public function portal(int $organizationId): array
    {
        $accounts = PatientPortalAccount::query()->where('organization_id', $organizationId);
        $released = PortalResultRelease::query()->where('organization_id', $organizationId);

        return [
            'accounts' => ['active' => (clone $accounts)->where('status', 'active')->count(), 'locked' => (clone $accounts)->whereIn('status', ['locked', 'suspended'])->count(), 'invited' => (clone $accounts)->whereIn('status', ['invited', 'pending'])->count(),
                'new_30d' => (clone $accounts)->where('activated_at', '>=', now()->subDays(30))->count(), 'logged_in_7d' => (clone $accounts)->where('last_login_at', '>=', now()->subDays(7))->count()],
            'results' => ['waiting_review' => (clone $released)->where('status', 'pending')->count(), 'held_critical' => (clone $released)->where('status', 'held_critical')->count(), 'withheld' => (clone $released)->whereIn('status', ['withheld', 'never'])->count(),
                'released_7d' => (clone $released)->where('status', 'released')->where('released_at', '>=', now()->subDays(7))->count()],
            'refills_open' => PortalRefillRequest::query()->where(['organization_id' => $organizationId, 'status' => 'requested'])->count(),
            'messages' => ['open' => PortalThread::query()->where('organization_id', $organizationId)->whereIn('status', ['open'])->count(), 'urgent' => PortalThread::query()->where(['organization_id' => $organizationId, 'urgent' => true, 'status' => 'open'])->count(),
                'overdue' => PortalThread::query()->where('organization_id', $organizationId)->where('status', 'open')->where('reply_due_at', '<', now())->count()],
            'requests_open' => PortalServiceRequest::query()->where('organization_id', $organizationId)->whereIn('status', ['requested', 'in_progress'])->count(),
            'proxies_waiting' => PortalProxy::query()->where(['organization_id' => $organizationId, 'status' => 'requested'])->count(),
            'payments' => ['pending' => PortalPayment::query()->where(['organization_id' => $organizationId])->whereIn('status', ['initiated', 'pending'])->count(), 'paid_30d' => (string) (PortalPayment::query()->where(['organization_id' => $organizationId, 'status' => 'succeeded'])->where('completed_at', '>=', now()->subDays(30))->sum('amount') ?: '0'),
                'to_refund' => PortalPayment::query()->where(['organization_id' => $organizationId, 'status' => 'succeeded'])->whereNull('billing_payment_id')->count(), 'unreconciled' => PortalPayment::query()->where(['organization_id' => $organizationId, 'status' => 'succeeded'])->whereNull('reconciled_at')->whereNotNull('billing_payment_id')->count()],
            'security_7d' => PortalSecurityEvent::query()->where('organization_id', $organizationId)->where('created_at', '>=', now()->subDays(7))->selectRaw('kind, count(*) c')->groupBy('kind')->pluck('c', 'kind')->all(),
            'outbox' => PortalOutbox::query()->where('organization_id', $organizationId)->where('created_at', '>=', now()->subDays(7))->selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status')->all(),
        ];
    }

    /** @return array<string, mixed> */
    public function crm(int $organizationId): array
    {
        $complaints = CrmComplaint::query()->where('organization_id', $organizationId);
        $closed = (clone $complaints)->whereNotNull('resolved_at')->get(['created_at', 'resolved_at']);

        return [
            'interactions_open' => CrmInteraction::query()->where(['organization_id' => $organizationId, 'status' => 'open'])->count(),
            'follow_ups_due' => CrmInteraction::query()->where(['organization_id' => $organizationId, 'status' => 'open'])->whereDate('follow_up_on', '<=', now()->toDateString())->count(),
            'complaints' => ['open' => (clone $complaints)->whereIn('status', ['open', 'assigned', 'investigating', 'escalated'])->count(), 'overdue' => (clone $complaints)->whereIn('status', ['open', 'assigned', 'investigating'])->where('due_at', '<', now())->count(),
                'escalated' => (clone $complaints)->where('status', 'escalated')->count(), 'avg_hours_to_resolve' => $closed->isEmpty() ? null : round($closed->avg(fn ($c) => $c->created_at->diffInHours($c->resolved_at, true)), 1),
                'satisfaction' => ($s = (clone $complaints)->whereNotNull('satisfaction')->avg('satisfaction')) ? round((float) $s, 2) : null,
                'by_category' => (clone $complaints)->selectRaw('category, count(*) c')->groupBy('category')->orderByDesc('c')->pluck('c', 'category')->all()],
            'feedback' => $this->feedback->summary($organizationId, now()->subDays(90)->toDateTimeString()),
            'feedback_unreviewed' => CrmFeedback::query()->where(['organization_id' => $organizationId, 'status' => 'new'])->where('rating', '<=', 2)->count(),
            'campaigns' => CrmCampaign::query()->where('organization_id', $organizationId)->selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status')->all(),
            'channels_30d' => CrmInteraction::query()->where('organization_id', $organizationId)->where('created_at', '>=', now()->subDays(30))->selectRaw('channel, count(*) c')->groupBy('channel')->pluck('c', 'channel')->all(),
            'portal_adoption' => $this->adoption($organizationId),
        ];
    }

    /** @return array{patients_seen_90d:int,with_portal:int,percent:float} */
    private function adoption(int $organizationId): array
    {
        $seen = DB::table('encounters')->where('organization_id', $organizationId)->where('encounter_date', '>=', now()->subDays(90)->toDateString())->distinct()->count('patient_id');
        $with = DB::table('encounters')->join('patient_portal_accounts as a', 'a.patient_id', '=', 'encounters.patient_id')->where('encounters.organization_id', $organizationId)->where('a.status', 'active')
            ->where('encounters.encounter_date', '>=', now()->subDays(90)->toDateString())->distinct()->count('encounters.patient_id');

        return ['patients_seen_90d' => $seen, 'with_portal' => $with, 'percent' => $seen > 0 ? round(100 * $with / $seen, 1) : 0.0];
    }
}
