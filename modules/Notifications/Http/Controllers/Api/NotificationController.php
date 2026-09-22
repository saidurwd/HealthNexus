<?php

namespace Modules\Notifications\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __invoke(Request $request, NotificationService $notifications): JsonResponse
    {
        $user = $request->user();

        return ApiResponse::success(
            $notifications->recentForUser($user, 20),
            'Notifications retrieved successfully.',
            200,
            ['unread' => $notifications->unreadForUser($user)->count()]
        );
    }

    public function markRead(Request $request, Notification $notification, NotificationService $notifications): JsonResponse
    {
        $this->authorize('view', $notification);

        $notification->markAsRead();

        return ApiResponse::success(null, 'Notification marked as read.');
    }
}
