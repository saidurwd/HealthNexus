<?php

namespace Modules\BloodBank\Http\Controllers\Web;

use App\Models\Blood\BloodIncident;
use App\Models\Blood\BloodIssue;
use App\Models\Blood\BloodRequest;
use App\Models\Blood\BloodStorageLocation;
use App\Models\Blood\BloodTemperatureLog;
use App\Models\Blood\BloodUnit;
use App\Services\Blood\BloodInventoryService;
use App\Services\Blood\BloodPolicy;
use Illuminate\Http\Request;

class BloodDashboardController extends BloodController
{
    public function index(Request $request, BloodInventoryService $inventory, BloodPolicy $policy)
    {
        $this->allow($request, 'dashboard.view');
        $org = $this->organizationId();

        return view('admin.blood.dashboard', [
            'summary' => $inventory->summary($org),
            'components' => array_keys($policy->get('components')),
            'labels' => collect($policy->get('components'))->map(fn ($c) => $c['label'])->all(),
            'low' => $inventory->lowStock($org),
            'nearExpiry' => BloodUnit::query()->where('organization_id', $org)->where('status', BloodUnit::AVAILABLE)->whereBetween('expires_at', [now(), now()->addDays((int) $policy->get('expiry_alert_days'))])->orderBy('expires_at')->limit(10)->get(),
            'urgent' => BloodRequest::query()->with('patient:id,public_id,first_name,last_name,enterprise_patient_no')->where('organization_id', $org)->whereIn('status', ['requested', 'sample_received', 'ready', 'partially_issued'])
                ->orderByRaw("case priority when 'massive' then 0 when 'emergency' then 1 when 'urgent' then 2 else 3 end")->orderBy('requested_at')->limit(10)->get(),
            'incidents' => BloodIncident::query()->where('organization_id', $org)->where('status', '!=', 'closed')->latest('occurred_at')->limit(5)->get(),
            'locations' => BloodStorageLocation::query()->where('organization_id', $org)->where('is_active', true)->orderBy('id')->get()->map(fn ($l) => ['location' => $l, 'last' => BloodTemperatureLog::where('location_id', $l->id)->latest('recorded_at')->first(), 'units' => BloodUnit::where('location_id', $l->id)->whereIn('status', ['available', 'reserved', 'quarantined'])->count()]),
            'issuedToday' => BloodIssue::query()->where('organization_id', $org)->whereDate('issued_at', today())->count(),
            'waiting' => BloodIssue::query()->where('organization_id', $org)->where('status', 'issued')->count(),
        ]);
    }
}
