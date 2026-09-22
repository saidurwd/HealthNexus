<?php

namespace Modules\Audit\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\LoginHistory;
use Illuminate\Http\Request;

class LoginHistoryController extends Controller
{
    public function index(Request $request)
    {
        $history = LoginHistory::query()
            ->with('user')
            ->when($request->filled('search'), function ($q, $search) {
                $q->where('email', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($q2) => $q2->where('name', 'like', "%{$search}%"));
            })
            ->when($request->filled('status') && $request->status !== 'all', fn ($q, $status) => $q->where('status', $request->status))
            ->latest('logged_in_at')
            ->paginate(50)
            ->appends($request->except('page'));

        return view('admin.security.login-history', compact('history'));
    }
}
