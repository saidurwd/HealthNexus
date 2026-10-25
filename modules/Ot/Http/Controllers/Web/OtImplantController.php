<?php

namespace Modules\Ot\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Ot\OtImplant;
use App\Models\Ot\OtSurgery;
use App\Services\AuditLogger;
use App\Services\Ot\OtImplantService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OtImplantController extends Controller
{
    public function __construct(
        private readonly OtImplantService $implants,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function store(Request $request, OtSurgery $surgery)
    {
        Gate::authorize('ot.implant.record');
        $this->authorize('view', $surgery);

        $validated = $request->validate([
            'implant_name' => ['required', 'string', 'max:255'],
            'manufacturer' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'lot_batch' => ['nullable', 'string', 'max:255'],
            'expiry_date' => ['nullable', 'date'],
            'quantity' => ['nullable', 'integer', 'min:1'],
        ]);

        $implant = $this->implants->record($surgery, $validated, $request->user());

        $this->auditLogger->log('CREATE', OtImplant::class, $implant->id, null, $implant->toArray(), $request);

        return back()->with('success', __('Implant recorded.'));
    }
}
