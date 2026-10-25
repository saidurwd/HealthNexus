<?php

namespace Modules\Ot\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Ot\OtSpecimen;
use App\Models\Ot\OtSurgery;
use App\Services\AuditLogger;
use App\Services\Ot\OtSpecimenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OtSpecimenController extends Controller
{
    public function __construct(
        private readonly OtSpecimenService $specimens,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function store(Request $request, OtSurgery $surgery)
    {
        Gate::authorize('ot.specimen.create');
        $this->authorize('view', $surgery);

        $validated = $request->validate([
            'specimen_type' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'site' => ['nullable', 'string', 'max:255'],
            'laterality' => ['nullable', 'string', 'max:50'],
            'container' => ['nullable', 'string', 'max:255'],
            'label' => ['nullable', 'string', 'max:255'],
            'destination' => ['nullable', 'string', 'max:255'],
            'send_to_lab' => ['nullable', 'boolean'],
        ]);

        $sendToLab = (bool) ($validated['send_to_lab'] ?? false);
        unset($validated['send_to_lab']);

        $specimen = $this->specimens->collect($surgery, $validated, $request->user(), $sendToLab);

        $this->auditLogger->log('CREATE', OtSpecimen::class, $specimen->id, null, $specimen->toArray(), $request);

        return back()->with('success', __('Specimen recorded.'));
    }
}
