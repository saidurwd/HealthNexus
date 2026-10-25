<?php

namespace Modules\Emergency\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Ed\EdBoardQuery;
use App\Services\TenantContextResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EdBoardController extends Controller
{
    public function __construct(private readonly EdBoardQuery $board, private readonly TenantContextResolver $tenant) {}

    public function index(Request $request)
    {
        abort_unless($request->user()->can('ed.board.view'), 403);

        return view('admin.ed.board', [
            'board' => $this->board->forBranch($this->tenant->getOrganizationId(), $this->tenant->getBranchId()),
            'bays' => $this->board->bays($this->tenant->getOrganizationId(), $this->tenant->getBranchId()),
            'pollSeconds' => 8,
            'labels' => ['waiting' => __('min'), 'unknown' => __('Unknown'), 'late' => __('Over target'), 'retriage' => __('Re-triage due'), 'triage' => __('Triage overdue'), 'mci' => __('Mass casualty'), 'none' => __('Not triaged'), 'total' => __('Total'), 'lost' => __('Connection lost — retrying')],
            'statusText' => collect(['awaiting_triage', 'waiting', 'in_treatment', 'observation', 'awaiting_disposition'])->mapWithKeys(fn ($s) => [$s => __(Str::headline($s))])->all(),
        ]);
    }

    /** Polled every few seconds by the board; scoped to the caller's organization and active branch. */
    public function data(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('ed.board.view'), 403);

        return response()->json(['board' => $this->board->forBranch($this->tenant->getOrganizationId(), $this->tenant->getBranchId()),
            'bays' => $this->board->bays($this->tenant->getOrganizationId(), $this->tenant->getBranchId())])->header('Cache-Control', 'no-store, private');
    }
}
