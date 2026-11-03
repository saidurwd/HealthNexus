<?php

namespace Modules\Facility\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Facility\FacAsset;
use App\Models\Facility\FacCleaningTask;
use App\Models\Facility\FacWorkOrder;
use App\Services\Facility\AssetService;
use App\Services\Facility\FacilityOverview;
use App\Services\TenantContextResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** The facility API: read-only. Scoped to the signed-in user and the hospital in the X-Organization-Id header (which the user must belong to). */
class FacilityApiController extends Controller
{
    public function __construct(private readonly TenantContextResolver $tenant, private readonly FacilityOverview $overview, private readonly AssetService $assets) {}

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
        return $this->ok($this->overview->summary($this->organization($request, 'facility.dashboard.view')));
    }

    /** May this equipment be used? Other systems ask this before putting a device to work. */
    public function asset(Request $request, string $tag): JsonResponse
    {
        $org = $this->organization($request, 'facility.asset.view');
        $asset = FacAsset::query()->where(['organization_id' => $org, 'tag' => strtoupper($tag)])->firstOrFail();
        $reason = $this->assets->reason($asset);

        return $this->ok(['tag' => $asset->tag, 'name' => $asset->name, 'status' => $asset->status, 'risk_class' => $asset->risk_class, 'usable' => $reason === null, 'reason' => $reason]);
    }

    public function workOrders(Request $request): JsonResponse
    {
        $org = $this->organization($request, 'facility.workorder.view');
        $f = $request->validate(['status' => ['nullable', 'string', 'max:14'], 'per_page' => ['nullable', 'integer', 'between:1,100']]);
        $page = FacWorkOrder::query()->with('asset:id,tag')->where('organization_id', $org)->when($f['status'] ?? null, fn ($q, $v) => $q->where('status', $v))->orderByDesc('id')->paginate($f['per_page'] ?? 25);

        return $this->ok($page->getCollection()->map(fn (FacWorkOrder $w) => ['id' => $w->public_id, 'number' => $w->number, 'kind' => $w->kind, 'priority' => $w->priority, 'status' => $w->status, 'asset' => $w->asset?->tag, 'due_on' => $w->due_on?->toDateString()])->values(),
            ['total' => $page->total(), 'page' => $page->currentPage(), 'per_page' => $page->perPage()]);
    }

    public function bedsToClean(Request $request): JsonResponse
    {
        $org = $this->organization($request, 'facility.housekeeping.view');

        return $this->ok(FacCleaningTask::query()->where('organization_id', $org)->whereIn('status', ['pending', 'in_progress', 'done'])->where('bed_released', false)->orderBy('requested_at')->get()
            ->map(fn (FacCleaningTask $t) => ['id' => $t->public_id, 'kind' => $t->kind, 'location' => $t->location, 'status' => $t->status, 'priority' => $t->priority, 'waiting_minutes' => (int) $t->requested_at->diffInMinutes(now())])->values());
    }
}
