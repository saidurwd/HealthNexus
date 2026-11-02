<?php

namespace Modules\Ai\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ai\AiOutput;
use App\Models\Ai\AiUseCase;
use App\Services\Ai\Monitoring;
use App\Services\TenantContextResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** The AI API is read-only: what functions exist and are on, the person's own drafts, and the figures that show how it is doing. It cannot ask the AI anything. */
class AiApiController extends Controller
{
    public function __construct(private readonly TenantContextResolver $tenant, private readonly Monitoring $monitoring) {}

    private function organization(Request $request, string ...$permissions): int
    {
        abort_unless(collect($permissions)->contains(fn ($p) => $request->user()->can($p)), 403);
        $org = (int) $this->tenant->getOrganizationId();
        abort_if($org === 0, 422, 'Say which hospital: send the X-Organization-Id header.');
        abort_unless($request->user()->organizations()->where('organizations.id', $org)->exists(), 403, 'You do not belong to that hospital.');

        return $org;
    }

    private function ok(mixed $data, array $meta = []): JsonResponse
    {
        return response()->json(['success' => true, 'message' => 'OK', 'data' => $data, 'meta' => $meta]);
    }

    public function useCases(Request $request): JsonResponse
    {
        $org = $this->organization($request, 'ai.use', 'ai.usecase.view');

        return $this->ok(AiUseCase::query()->where('organization_id', $org)->orderBy('id')->get()->map(fn ($u) => ['code' => $u->code, 'name' => $u->name, 'risk_class' => $u->risk_class, 'status' => $u->status, 'implemented' => $u->implemented])->values());
    }

    public function outputs(Request $request): JsonResponse
    {
        $org = $this->organization($request, 'ai.use', 'ai.output.view_all');
        $all = $request->user()->can('ai.output.view_all') && $request->query('show') === 'all';
        $page = AiOutput::query()->where('organization_id', $org)->when(! $all, fn ($q) => $q->where('requested_by', $request->user()->id))->orderByDesc('id')->paginate(min(100, (int) $request->query('per_page', 25)));

        return $this->ok($page->getCollection()->map(fn (AiOutput $o) => ['id' => $o->public_id, 'use_case' => $o->use_case_code, 'kind' => $o->kind, 'status' => $o->status, 'label' => $o->label, 'created_at' => $o->created_at->toIso8601String()])->values(), ['total' => $page->total(), 'page' => $page->currentPage()]);
    }

    public function metrics(Request $request): JsonResponse
    {
        $org = $this->organization($request, 'ai.monitor.view');

        return $this->ok(AiUseCase::query()->where('organization_id', $org)->where('implemented', true)->get()->mapWithKeys(fn ($u) => [$u->code => $this->monitoring->metrics($org, $u->code)]));
    }
}
