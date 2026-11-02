<?php

namespace Modules\Welfare\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Welfare\WfFund;
use App\Services\TenantContextResolver;
use App\Services\Welfare\FundService;
use App\Services\Welfare\WelfareOverview;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** The welfare API is read-only and carries totals and fund balances only: no patient, income or circumstance leaves through it. */
class WelfareApiController extends Controller
{
    public function __construct(private readonly TenantContextResolver $tenant, private readonly WelfareOverview $overview, private readonly FundService $funds) {}

    private function organization(Request $request, string $permission): int
    {
        abort_unless($request->user()->can($permission), 403);
        $org = (int) $this->tenant->getOrganizationId();
        abort_if($org === 0, 422, 'Say which hospital: send the X-Organization-Id header.');
        abort_unless($request->user()->organizations()->where('organizations.id', $org)->exists(), 403, 'You do not belong to that hospital.');

        return $org;
    }

    private function ok(mixed $data): JsonResponse
    {
        return response()->json(['success' => true, 'message' => 'OK', 'data' => $data, 'meta' => (object) []]);
    }

    public function overview(Request $request): JsonResponse
    {
        $s = $this->overview->summary($this->organization($request, 'welfare.dashboard.view'));
        unset($s['funds']);

        return $this->ok($s);
    }

    public function funds(Request $request): JsonResponse
    {
        $org = $this->organization($request, 'welfare.fund.view');

        return $this->ok(WfFund::query()->where('organization_id', $org)->orderBy('name')->get()->map(fn (WfFund $f) => ['id' => $f->public_id, 'name' => $f->name, 'kind' => $f->kind, 'status' => $f->status, 'balance' => $this->funds->balance($f),
            'committed' => $this->funds->committed($f), 'available' => $this->funds->available($f)])->all());
    }
}
