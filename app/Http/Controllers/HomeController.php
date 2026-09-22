<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogger;
use App\Services\Dashboard\DashboardWidgetRegistry;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    public function __construct(
        private TenantContextResolver $resolver,
        private ActivityLogger $activityLogger,
        private DashboardWidgetRegistry $widgets,
    ) {}

    public function index(Request $request)
    {
        $user = Auth::user();

        $companies = $user->companies()->get();
        $currentCompany = $this->resolver->getCompany();
        $currentBranch = $this->resolver->getBranch();
        $currentDepartment = $this->resolver->getDepartment();
        $widgets = $this->widgets->forUser($user);

        $this->activityLogger->log('DASHBOARD_VIEWED', 'Viewed dashboard', null, [], $request);

        return view('dashboard', [
            'userCompanies' => $companies,
            'currentCompany' => $currentCompany,
            'currentBranch' => $currentBranch,
            'currentDepartment' => $currentDepartment,
            'widgets' => $widgets,
        ])->with('page_title', 'Dashboard');
    }
}
