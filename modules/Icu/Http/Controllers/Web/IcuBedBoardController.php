<?php

namespace Modules\Icu\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Icu\IcuBedBoardService;
use App\Services\TenantContextResolver;
use Illuminate\Support\Facades\Gate;

class IcuBedBoardController extends Controller
{
    public function __construct(private readonly IcuBedBoardService $bedBoard) {}

    public function index()
    {
        Gate::authorize('icu.bed.view');

        $organizationId = app(TenantContextResolver::class)->getOrganizationId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $board = $this->bedBoard->board($organizationId, $branchId);

        return view('admin.icu.bed-board.index', compact('board'));
    }
}
