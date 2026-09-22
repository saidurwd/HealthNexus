<?php

namespace Modules\Audit\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\SecurityEvent;
use Illuminate\Http\Request;
use Modules\Audit\Http\Requests\ResolveSecurityEventRequest;

class SecurityEventController extends Controller
{
    public function index(Request $request)
    {
        $events = SecurityEvent::query()
            ->with('user')
            ->when($request->filled('search'), function ($q, $search) {
                $q->where('event', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($q2) => $q2->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"));
            })
            ->when($request->filled('severity') && $request->severity !== 'all', fn ($q, $severity) => $q->where('severity', $severity))
            ->when($request->filled('unresolved'), fn ($q) => $q->whereNull('resolved_at'))
            ->latest()
            ->paginate(50)
            ->appends($request->except('page'));

        return view('admin.security.index', compact('events'));
    }

    public function show(SecurityEvent $securityEvent)
    {
        $securityEvent->load('user', 'company', 'branch');

        return view('admin.security.show', compact('securityEvent'));
    }

    public function resolve(Request $request, SecurityEvent $securityEvent, ResolveSecurityEventRequest $formRequest)
    {
        $validated = $formRequest->validated();

        $securityEvent->update(array_merge($validated, [
            'resolved_at' => now(),
        ]));

        return back()->with('success', 'Security event resolved.');
    }
}
