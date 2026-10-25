<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Services\Terminology\CodeSearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Read-only picker search over the Code System Registry (platform data, not tenant data). */
class CodeSearchController extends Controller
{
    public function __invoke(Request $request, CodeSearch $search): JsonResponse
    {
        $validated = $request->validate([
            'system' => ['required', 'string', 'exists:code_systems,key'],
            'q' => ['nullable', 'string', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        return ApiResponse::success($search->search($validated['system'], $validated['q'] ?? '', $validated['limit'] ?? 20));
    }
}
