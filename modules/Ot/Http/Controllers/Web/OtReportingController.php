<?php

namespace Modules\Ot\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Ot\OtSurgery;
use App\Models\Ot\OtSurgeryScheduleEvent;
use App\Services\TenantContextResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class OtReportingController extends Controller
{
    public function surgeries()
    {
        Gate::authorize('ot.reports.view');

        $companyId = app(TenantContextResolver::class)->getCompanyId();

        $byStatus = OtSurgery::where('company_id', $companyId)
            ->select('status', DB::raw('count(*) as total'))->groupBy('status')->pluck('total', 'status');
        $bySpecialty = OtSurgery::where('ot_surgeries.company_id', $companyId)
            ->join('specialties', 'specialties.id', '=', 'ot_surgeries.specialty_id')
            ->select('specialties.name', DB::raw('count(*) as total'))->groupBy('specialties.name')->pluck('total', 'name');

        return view('admin.ot.reports.surgeries', compact('byStatus', 'bySpecialty'));
    }

    public function utilization()
    {
        Gate::authorize('ot.reports.view');

        $companyId = app(TenantContextResolver::class)->getCompanyId();

        $cancellations = OtSurgeryScheduleEvent::join('ot_surgeries', 'ot_surgeries.id', '=', 'ot_surgery_schedule_events.surgery_id')
            ->where('ot_surgeries.company_id', $companyId)
            ->select('change_type', DB::raw('count(*) as total'))->groupBy('change_type')->pluck('total', 'change_type');

        $delays = OtSurgeryScheduleEvent::join('ot_surgeries', 'ot_surgeries.id', '=', 'ot_surgery_schedule_events.surgery_id')
            ->where('ot_surgeries.company_id', $companyId)
            ->where('ot_surgery_schedule_events.change_type', OtSurgeryScheduleEvent::TYPE_DELAYED)
            ->select('delay_category', DB::raw('count(*) as total'), DB::raw('avg(delay_minutes) as avg_minutes'))
            ->groupBy('delay_category')->get();

        return view('admin.ot.reports.utilization', compact('cancellations', 'delays'));
    }

    public function complications()
    {
        Gate::authorize('ot.reports.view');

        $companyId = app(TenantContextResolver::class)->getCompanyId();

        $complications = \App\Models\Ot\OtSurgicalComplication::join('ot_surgeries', 'ot_surgeries.id', '=', 'ot_surgical_complications.surgery_id')
            ->where('ot_surgeries.company_id', $companyId)
            ->select('severity', DB::raw('count(*) as total'))->groupBy('severity')->pluck('total', 'severity');

        return view('admin.ot.reports.complications', compact('complications'));
    }
}
