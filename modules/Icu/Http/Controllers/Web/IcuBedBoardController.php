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

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $board = $this->bedBoard->board($companyId, $branchId);

        return view('admin.icu.bed-board.index', compact('board'));
    }
}
