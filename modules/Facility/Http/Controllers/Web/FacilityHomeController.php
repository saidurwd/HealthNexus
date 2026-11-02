<?php

namespace Modules\Facility\Http\Controllers\Web;

use App\Services\Facility\FacilityOverview;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;

class FacilityHomeController extends FacilityController
{
    public function __construct(TenantContextResolver $tenant, private readonly FacilityOverview $overview)
    {
        parent::__construct($tenant);
    }

    public function index(Request $request)
    {
        $this->allow($request, 'facility.dashboard.view', 'facility.workorder.report');

        return view('admin.facility.index', ['s' => $request->user()->can('facility.dashboard.view') ? $this->overview->summary($this->organizationId(), (int) config('facility.dashboard.default_days', 30)) : null]);
    }
}
