<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogger;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    public function __construct(
        private TenantContextResolver $resolver,
        private ActivityLogger $activityLogger,
    ) {}

    public function index(Request $request)
    {
        $user = Auth::user();

        $companies = $user->companies()->get();
        $currentCompany = $this->resolver->getCompany();
        $currentBranch = $this->resolver->getBranch();
        $currentDepartment = $this->resolver->getDepartment();

        $this->activityLogger->log('DASHBOARD_VIEWED', 'Viewed dashboard', null, [], $request);

        return view('dashboard', [
            'userCompanies' => $companies,
            'currentCompany' => $currentCompany,
            'currentBranch' => $currentBranch,
            'currentDepartment' => $currentDepartment,
        ])->with('page_title', 'Dashboard');
    }
}
