<?php

namespace Modules\Analytics\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Analytics\AnalyticsKpi;
use App\Services\Analytics\Kpi\KpiService;
use App\Services\Analytics\Semantic\ScopeContext;
use App\Services\Analytics\Semantic\SemanticCatalog;
use App\Services\Analytics\Semantic\SemanticQuery;
use App\Services\TenantContextResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** The analytics API: the same semantic model as the screens, read-only. Each call is scoped to the signed-in user and the hospital in the X-Organization-Id header (which the user must belong to). */
class AnalyticsApiController extends Controller
{
    public function __construct(private readonly TenantContextResolver $tenant, private readonly SemanticQuery $queries, private readonly KpiService $kpis) {}

    private function scope(Request $request, string $permission): ScopeContext
    {
        abort_unless($request->user()->can($permission), 403);
        $org = (int) $this->tenant->getOrganizationId();
        abort_if($org === 0, 422, 'Say which hospital: send the X-Organization-Id header.');
        abort_unless($request->user()->organizations()->where('organizations.id', $org)->exists(), 403, 'You do not belong to that hospital.');

        return ScopeContext::for($request->user(), $org);
    }

    private function ok(array $data, array $meta = []): JsonResponse
    {
        return response()->json(['success' => true, 'message' => 'OK', 'data' => $data, 'meta' => $meta]);
    }

    public function catalog(Request $request): JsonResponse
    {
        $this->scope($request, 'analytics.query.run');

        return $this->ok(SemanticCatalog::describe());
    }

    public function kpis(Request $request): JsonResponse
    {
        $scope = $this->scope($request, 'analytics.kpi.view');
        $r = $request->validate(['from' => ['nullable', 'date'], 'to' => ['nullable', 'date']]);

        return $this->ok($this->kpis->forOrganization($scope->organizationId)->map(fn (AnalyticsKpi $k) => ['code' => $k->code, 'name' => $k->name, 'unit' => $k->unit, 'version' => $k->version] + $this->kpis->evaluate($k, $scope, $r['from'] ?? null, $r['to'] ?? null))->values()->all());
    }

    public function query(Request $request): JsonResponse
    {
        $scope = $this->scope($request, 'analytics.query.run');
        $q = $request->validate(['fact' => ['required', 'string'], 'measures' => ['required', 'array', 'min:1', 'max:6'], 'measures.*' => ['string'], 'group_by' => ['nullable', 'array', 'max:2'], 'group_by.*' => ['string'], 'grain' => ['nullable', 'in:day,month,year'], 'filters' => ['nullable', 'array', 'max:10'],
            'from' => ['nullable', 'date'], 'to' => ['nullable', 'date'], 'order_by' => ['nullable', 'string'], 'direction' => ['nullable', 'in:asc,desc'], 'limit' => ['nullable', 'integer', 'between:1,1000']]);
        $r = $this->queries->run($q, $scope);

        return $this->ok($r['rows'], ['columns' => $r['columns']] + array_diff_key($r['meta'], ['sql' => 1]));
    }
}
