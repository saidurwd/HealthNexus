<?php

namespace Modules\Audit\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $logs = ActivityLog::query()
            ->with('user')
            ->when($request->filled('search'), function ($q, $search) {
                $q->where('action', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($q2) => $q2->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"));
            })
            ->when($request->filled('action') && $request->action !== 'all', fn ($q, $action) => $q->where('action', $action))
            ->latest()
            ->paginate(50)
            ->appends($request->except('page'));

        return view('admin.activity.index', compact('logs'));
    }

    public function show(ActivityLog $activityLog)
    {
        $activityLog->load('user', 'company', 'branch', 'entity');

        return view('admin.activity.show', compact('activityLog'));
    }
}
