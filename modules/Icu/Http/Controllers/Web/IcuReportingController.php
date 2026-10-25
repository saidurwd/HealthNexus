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

        $companyId = app(TenantContextResolver::class)->getCompanyId();

        $byStatus = IcuEpisode::where('icu_episodes.company_id', $companyId)
            ->select('status', DB::raw('count(*) as total'))->groupBy('status')->pluck('total', 'status');

        return view('admin.icu.reports.occupancy', compact('byStatus'));
    }

    public function utilization()
    {
        Gate::authorize('icu.reports.view');

        $companyId = app(TenantContextResolver::class)->getCompanyId();

        $admissionsPerDay = IcuEpisode::where('icu_episodes.company_id', $companyId)
            ->select(DB::raw('DATE(admitted_at) as day'), DB::raw('count(*) as total'))
            ->groupBy('day')->orderByDesc('day')->limit(30)->get();

        return view('admin.icu.reports.utilization', compact('admissionsPerDay'));
    }

    public function outcomes()
    {
        Gate::authorize('icu.reports.view');

        $companyId = app(TenantContextResolver::class)->getCompanyId();

        $criticalEvents = IcuCriticalEvent::join('icu_episodes', 'icu_episodes.id', '=', 'icu_critical_events.episode_id')
            ->where('icu_episodes.company_id', $companyId)
            ->select('icu_critical_events.event_type', DB::raw('count(*) as total'))
            ->groupBy('icu_critical_events.event_type')->pluck('total', 'event_type');

        $byDestination = IcuEpisode::where('icu_episodes.company_id', $companyId)
            ->whereNotNull('discharged_at')
            ->select(DB::raw("'discharged' as destination"), DB::raw('count(*) as total'))
            ->groupBy('destination')->pluck('total', 'destination');

        return view('admin.icu.reports.outcomes', compact('criticalEvents', 'byDestination'));
    }
}
