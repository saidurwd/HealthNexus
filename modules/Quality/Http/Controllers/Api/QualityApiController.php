<?php

namespace Modules\Quality\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Quality\QaAction;
use App\Models\Quality\QaIncident;
use App\Services\Quality\QualityOverview;
use App\Services\Quality\QualityScope;
use App\Services\TenantContextResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** The quality API: read-only figures and lists, scoped to the signed-in user and the hospital in the X-Organization-Id header (which the user must belong to). It never carries a reporter. */
class QualityApiController extends Controller
{
    public function __construct(private readonly TenantContextResolver $tenant, private readonly QualityOverview $overview, private readonly QualityScope $scope) {}

    private function organization(Request $request, string $permission): int
    {
        abort_unless($request->user()->can($permission), 403);
        $org = (int) $this->tenant->getOrganizationId();
        abort_if($org === 0, 422, 'Say which hospital: send the X-Organization-Id header.');
        abort_unless($request->user()->organizations()->where('organizations.id', $org)->exists(), 403, 'You do not belong to that hospital.');

        return $org;
    }

    private function ok(mixed $data, array $meta = []): JsonResponse
    {
        return response()->json(['success' => true, 'message' => 'OK', 'data' => $data, 'meta' => $meta]);
    }

    public function overview(Request $request): JsonResponse
    {
        $org = $this->organization($request, 'quality.dashboard.view');
        $s = $this->overview->summary($request->user(), $org);
        unset($s['indicators']);

        return $this->ok($s);
    }

    public function indicators(Request $request): JsonResponse
    {
        $org = $this->organization($request, 'quality.indicator.view');

        return $this->ok(collect($this->overview->latestIndicators($org))->map(fn ($r) => ['code' => $r['indicator']->code, 'name' => $r['indicator']->name, 'unit' => $r['indicator']->unit, 'good_direction' => $r['indicator']->good_direction,
            'target' => $r['indicator']->target === null ? null : (float) $r['indicator']->target, 'value' => $r['value'], 'previous' => $r['previous'], 'on_target' => $r['on_target'], 'period' => $r['period']])->values());
    }

    public function incidents(Request $request): JsonResponse
    {
        $org = $this->organization($request, 'quality.incident.view');
        $f = $request->validate(['status' => ['nullable', 'string', 'max:14'], 'category' => ['nullable', 'string', 'max:24'], 'per_page' => ['nullable', 'integer', 'between:1,100']]);
        $page = $this->scope->apply(QaIncident::query(), $request->user(), $org)->with('amendments')->when($f['status'] ?? null, fn ($q, $v) => $q->where('status', $v))->when($f['category'] ?? null, fn ($q, $v) => $q->where('category', $v))
            ->orderByDesc('occurred_at')->paginate($f['per_page'] ?? 25);

        return $this->ok($page->getCollection()->map(fn (QaIncident $i) => ['id' => $i->public_id, 'number' => $i->number, 'kind' => $i->kind, 'category' => $i->effective('category'), 'harm' => $i->harmNow(), 'status' => $i->status,
            'occurred_at' => $i->occurred_at->toIso8601String(), 'due_on' => $i->due_on?->toDateString(), 'anonymous' => $i->is_anonymous])->values(), ['total' => $page->total(), 'page' => $page->currentPage(), 'per_page' => $page->perPage()]);
    }

    public function actions(Request $request): JsonResponse
    {
        $org = $this->organization($request, 'quality.action.view');
        $f = $request->validate(['status' => ['nullable', 'string', 'max:12'], 'per_page' => ['nullable', 'integer', 'between:1,100']]);
        $page = $this->scope->apply(QaAction::query(), $request->user(), $org)->when($f['status'] ?? null, fn ($q, $v) => $q->where('status', $v))->orderBy('due_on')->paginate($f['per_page'] ?? 25);

        return $this->ok($page->getCollection()->map(fn (QaAction $a) => ['id' => $a->public_id, 'number' => $a->number, 'type' => $a->action_type, 'status' => $a->status, 'due_on' => $a->due_on->toDateString(), 'source' => $a->source_type])->values(),
            ['total' => $page->total(), 'page' => $page->currentPage(), 'per_page' => $page->perPage()]);
    }
}
