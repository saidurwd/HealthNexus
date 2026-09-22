<?php

namespace Modules\Settings\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function __invoke(Request $request, SettingsService $settings): JsonResponse
    {
        return ApiResponse::success($settings->all(), 'Settings retrieved successfully.');
    }
}
