<?php

namespace Modules\Icu\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Icu\IcuCriticalEvent;
use App\Models\Icu\IcuEpisode;
use App\Services\TenantContextResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class IcuReportingController extends Controller
{
    public function occupancy()
    {
        Gate::authorize('icu.reports.view');

        $organizationId = app(TenantContextResolver::class)->getOrganizationId();

        $byStatus = IcuEpisode::where('icu_episodes.organization_id', $organizationId)
            ->select('status', DB::raw('count(*) as total'))->groupBy('status')->pluck('total', 'status');

        return view('admin.icu.reports.occupancy', compact('byStatus'));
    }

    public function utilization()
    {
        Gate::authorize('icu.reports.view');

        $organizationId = app(TenantContextResolver::class)->getOrganizationId();

        $admissionsPerDay = IcuEpisode::where('icu_episodes.organization_id', $organizationId)
            ->select(DB::raw('DATE(admitted_at) as day'), DB::raw('count(*) as total'))
            ->groupBy('day')->orderByDesc('day')->limit(30)->get();

        return view('admin.icu.reports.utilization', compact('admissionsPerDay'));
    }

    public function outcomes()
    {
        Gate::authorize('icu.reports.view');

        $organizationId = app(TenantContextResolver::class)->getOrganizationId();

        $criticalEvents = IcuCriticalEvent::join('icu_episodes', 'icu_episodes.id', '=', 'icu_critical_events.episode_id')
            ->where('icu_episodes.organization_id', $organizationId)
            ->select('icu_critical_events.event_type', DB::raw('count(*) as total'))
            ->groupBy('icu_critical_events.event_type')->pluck('total', 'event_type');

        $byDestination = IcuEpisode::where('icu_episodes.organization_id', $organizationId)
            ->whereNotNull('discharged_at')
            ->select(DB::raw("'discharged' as destination"), DB::raw('count(*) as total'))
            ->groupBy('destination')->pluck('total', 'destination');

        return view('admin.icu.reports.outcomes', compact('criticalEvents', 'byDestination'));
    }
}
