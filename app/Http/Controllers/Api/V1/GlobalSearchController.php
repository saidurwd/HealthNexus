<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Services\Search\GlobalSearchService;
use App\Services\Search\ScanResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GlobalSearchController extends Controller
{
    public function search(Request $request, GlobalSearchService $search): JsonResponse
    {
        $validated = $request->validate(['q' => ['required', 'string', 'max:100'], 'limit' => ['nullable', 'integer', 'min:1', 'max:30']]);

        return ApiResponse::success($search->search($request->user(), $validated['q'], $validated['limit'] ?? 15));
    }

    /** A scanner "types" the code and presses Enter; the answer says what it is and where to go. */
    public function scan(Request $request, ScanResolver $scan): JsonResponse
    {
        $validated = $request->validate(['code' => ['required', 'string', 'max:120']]);

        $hit = $scan->resolve($request->user(), $validated['code']);

        return $hit ? ApiResponse::success($hit) : ApiResponse::notFound('Nothing matches this code.');
    }
}
