<?php

namespace Modules\Clinical\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Encounter;
use Illuminate\Http\Request;

class ClinicalReportController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $encounters = Encounter::query()
            ->when(! $user->hasRole('super_admin'), fn ($q) => $q->whereIn('company_id', $user->companies()->pluck('companies.id')))
            ->get();

        $stats = [
            'total' => $encounters->count(),
            'by_status' => $encounters->groupBy('status')->map->count(),
            'by_type' => $encounters->groupBy('encounter_type')->map->count(),
            'locked' => $encounters->where('locked_at', '!=', null)->count(),
            'completed' => $encounters->where('status', 'completed')->count(),
        ];

        return view('admin.reports.clinical', compact('stats', 'encounters'));
    }
}
