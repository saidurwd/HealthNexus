<?php

namespace Modules\BloodBank\Http\Controllers\Web;

use App\Models\Blood\BloodDonor;
use App\Models\Blood\BloodIncident;
use App\Models\Blood\BloodRecall;
use App\Models\Blood\BloodUnit;
use App\Services\AuditLogger;
use App\Services\Blood\BloodIncidentService;
use App\Services\Blood\BloodTraceabilityService;
use App\Services\Blood\BloodUtilizationReport;
use App\Services\TenantContextResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Incidents, lookback, recall, and the utilization / wastage report. */
class BloodSafetyController extends BloodController
{
    public function __construct(TenantContextResolver $tenant, private readonly BloodTraceabilityService $trace, private readonly AuditLogger $audit)
    {
        parent::__construct($tenant);
    }

    public function incidents(Request $request)
    {
        $this->allow($request, 'incident.view');

        return view('admin.blood.safety.incidents', ['incidents' => BloodIncident::query()->where('organization_id', $this->organizationId())->orderByRaw("status = 'closed'")->latest('occurred_at')->paginate(25)]);
    }

    public function closeIncident(Request $request, BloodIncident $incident, BloodIncidentService $incidents): RedirectResponse
    {
        $this->scoped($request, $incident, 'incident.manage');
        $validated = $request->validate(['resolution' => ['required', 'string', 'max:1000']]);
        $incidents->close($incident, $validated['resolution'], $request->user());

        return back()->with('success', __('Incident closed.'));
    }

    /** "Who received units from donor X?" and "which donor gave unit Y?" — both answered on one screen. */
    public function lookback(Request $request)
    {
        $this->allow($request, 'lookback.view');
        $org = $this->organizationId();
        $term = trim((string) $request->query('q'));
        $donor = null;
        $unit = null;
        $recipients = collect();
        $link = null;
        if ($term !== '') {
            $donor = BloodDonor::query()->with('patient')->where('organization_id', $org)->where(fn ($q) => $q->where('donor_number', $term)->orWhere('public_id', $term))->first();
            $unit = BloodUnit::query()->where('organization_id', $org)->where(fn ($q) => $q->where('unit_number', $term)->orWhere('public_id', $term))->first();
            $donor && $recipients = $this->trace->recipientsOfDonor($donor);
            if ($unit) {
                $recipients = $this->trace->recipientsOfUnit($unit);
                $link = $this->trace->donorOfUnit($unit);
            }
            $this->audit->log('BLOOD_LOOKBACK', BloodUnit::class, $unit?->id ?? $donor?->id ?? 0, null, ['query' => $term], $request);
        }

        return view('admin.blood.safety.lookback', ['q' => $term, 'donor' => $donor, 'unit' => $unit, 'recipients' => $recipients, 'link' => $link, 'recalls' => BloodRecall::query()->where('organization_id', $org)->latest('initiated_at')->limit(10)->get()]);
    }

    public function recall(Request $request): RedirectResponse
    {
        $this->allow($request, 'recall.manage');
        $validated = $request->validate(['donor' => ['required', 'string', 'max:40'], 'reason' => ['required', 'string', 'max:500']]);
        $donor = BloodDonor::query()->where('organization_id', $this->organizationId())->where(fn ($q) => $q->where('donor_number', $validated['donor'])->orWhere('public_id', $validated['donor']))->firstOrFail();
        $recall = $this->trace->recall($request->user(), $validated['reason'], $donor);
        $this->audit->log('BLOOD_RECALL', BloodRecall::class, $recall->id, null, ['blocked' => $recall->units_blocked, 'recipients' => $recall->recipients_found], $request);

        return redirect()->route('admin.blood.lookback', ['q' => $donor->donor_number])->with('warning', __('Recall done: :b bag(s) blocked, :r recipient(s) to inform.', ['b' => $recall->units_blocked, 'r' => $recall->recipients_found]));
    }

    public function reports(Request $request, BloodUtilizationReport $report)
    {
        $this->allow($request, 'report.view');
        $days = (int) $request->query('days', 30);
        $days = in_array($days, [7, 30, 90, 180], true) ? $days : 30;

        return view('admin.blood.safety.reports', ['days' => $days, 'summary' => $report->summary($this->organizationId(), now()->subDays($days), now()), 'labels' => collect(config('blood.components'))->map(fn ($c) => $c['label'])->all()]);
    }
}
