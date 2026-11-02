<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogger;
use App\Services\Dashboard\DashboardCache;
use App\Services\Dashboard\DashboardWidgetRegistry;
use App\Services\Dashboard\HomeTiles;
use App\Services\TenantContextResolver;
use App\Services\Ux\RoleHome;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    public function __construct(
        private TenantContextResolver $resolver,
        private ActivityLogger $activityLogger,
        private DashboardWidgetRegistry $widgets,
        private HomeTiles $tiles,
        private DashboardCache $cache,
        private RoleHome $roleHome,
    ) {}

    public function index(Request $request)
    {
        $user = Auth::user();

        $organizations = $user->organizations()->get();
        $currentOrganization = $this->resolver->getOrganization();
        $currentBranch = $this->resolver->getBranch();
        $currentDepartment = $this->resolver->getDepartment();
        $widgets = $this->widgets->forUser($user);

        $this->activityLogger->log('DASHBOARD_VIEWED', 'Viewed dashboard', null, [], $request);

        // What is waiting for this person. Worked out for this person (some counts are their own), kept for a minute.
        $org = $currentOrganization ? (int) $currentOrganization->id : 0;
        $waiting = $this->cache->remember($user, 'home:u'.$user->id, $org, $currentBranch ? (int) $currentBranch->id : null, null, fn () => $this->tiles->forUser($user, $org), fresh: $request->boolean('fresh'));
        $home = $this->roleHome->urlFor($user);

        return view('dashboard', [
            'userOrganizations' => $organizations,
            'currentOrganization' => $currentOrganization,
            'currentBranch' => $currentBranch,
            'currentDepartment' => $currentDepartment,
            'widgets' => $widgets,
            'tiles' => $waiting['data'],
            'asOf' => $waiting['as_of'],
            'worklistUrl' => $home !== route('home') ? $home : null,
        ])->with('page_title', 'Dashboard');
    }
}
