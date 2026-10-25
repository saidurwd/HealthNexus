<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\ReportExport;
use App\Services\Reports\ReportExportService;
use App\Services\TenantContextResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportController extends Controller
{
    public function __construct(private readonly ReportExportService $exports, private readonly TenantContextResolver $tenant) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'report' => ['required', 'string', 'max:60'],
            'format' => ['required', 'string', 'max:10'],
            'filters' => ['nullable', 'array'],
        ]);

        $export = $this->exports->request($request->user(), $validated['report'], $validated['format'], $validated['filters'] ?? []);

        return ApiResponse::success($this->present($export), 'Export queued', 202);
    }

    public function show(Request $request, ReportExport $export): JsonResponse
    {
        $this->assertVisible($request, $export);

        return ApiResponse::success($this->present($export));
    }

    public function download(Request $request, ReportExport $export): StreamedResponse
    {
        $this->assertVisible($request, $export);

        return $this->exports->download($export, $request->user());
    }

    /** Another organization's export does not exist as far as this caller can tell (404, not 403). */
    private function assertVisible(Request $request, ReportExport $export): void
    {
        abort_unless($export->organization_id === $this->tenant->getOrganizationId(), 404);
        abort_unless($export->user_id === $request->user()->id || $request->user()->can('reporting.export'), 404);
    }

    private function present(ReportExport $export): array
    {
        return [
            'id' => $export->public_id, 'report' => $export->report_key, 'format' => $export->format, 'status' => $export->status,
            'rows' => $export->row_count, 'requested_at' => $export->requested_at, 'expires_at' => $export->expires_at,
        ];
    }
}
