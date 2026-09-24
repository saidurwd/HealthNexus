<?php

namespace Modules\Nursing\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Nursing\NursingDevice;
use App\Models\Nursing\NursingEpisode;
use App\Models\Nursing\NursingWoundAssessment;
use App\Services\AuditLogger;
use App\Services\Nursing\NursingDeviceService;
use App\Services\Nursing\NursingEducationService;
use App\Services\Nursing\NursingIvMonitoringService;
use App\Services\Nursing\NursingWoundAssessmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Bedside clinical documentation grouped into one controller: IV/infusion monitoring, lines/
 * tubes/devices, wound/skin assessment, and patient education — each a small, independent form
 * attached to the same episode.
 */
class NursingClinicalController extends Controller
{
    public function __construct(
        private readonly NursingIvMonitoringService $iv,
        private readonly NursingDeviceService $devices,
        private readonly NursingWoundAssessmentService $wounds,
        private readonly NursingEducationService $education,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(NursingEpisode $episode)
    {
        Gate::authorize('nursing.device.view');

        $episode->load(['patient']);
        $devices = NursingDevice::where('episode_id', $episode->id)->latest('insertion_date')->get();
        $infusions = \App\Models\Nursing\NursingIvInfusion::where('episode_id', $episode->id)->latest('start_time')->get();
        $wounds = NursingWoundAssessment::where('episode_id', $episode->id)->latest('assessed_at')->get();
        $education = \App\Models\Nursing\NursingEducation::where('episode_id', $episode->id)->latest('provided_at')->get();

        return view('admin.nursing.clinical.index', compact('episode', 'devices', 'infusions', 'wounds', 'education'));
    }

    public function storeDevice(Request $request, NursingEpisode $episode)
    {
        Gate::authorize('nursing.device.create');

        $validated = $request->validate([
            'device_type' => ['required', 'string', 'max:255'],
            'insertion_date' => ['required', 'date'],
            'site' => ['nullable', 'string', 'max:255'],
            'care_schedule' => ['nullable', 'string', 'max:255'],
        ]);

        $device = $this->devices->insert($episode, $validated, $request->user());

        $this->auditLogger->log('CREATE', NursingDevice::class, $device->id, null, $device->toArray(), $request);

        return back()->with('success', 'Device recorded.');
    }

    public function removeDevice(Request $request, NursingDevice $device)
    {
        Gate::authorize('nursing.device.update');

        $this->devices->remove($device, $request->user(), $request->input('complication'));

        $this->auditLogger->log('UPDATE', NursingDevice::class, $device->id, null, ['status' => 'removed'], $request);

        return back()->with('success', 'Device removed.');
    }

    public function storeIv(Request $request, NursingEpisode $episode)
    {
        Gate::authorize('nursing.iv.create');

        $validated = $request->validate([
            'fluid_name' => ['required', 'string', 'max:255'],
            'rate' => ['nullable', 'string', 'max:255'],
            'start_time' => ['required', 'date'],
            'expected_completion' => ['nullable', 'date'],
            'site' => ['nullable', 'string', 'max:255'],
            'device_id' => ['nullable', 'integer', 'exists:nursing_devices,id'],
        ]);

        $infusion = $this->iv->start($episode, $validated, $request->user());

        $this->auditLogger->log('CREATE', \App\Models\Nursing\NursingIvInfusion::class, $infusion->id, null, $infusion->toArray(), $request);

        return back()->with('success', 'IV infusion started.');
    }

    public function storeWound(Request $request, NursingEpisode $episode)
    {
        Gate::authorize('nursing.wound.create');

        $validated = $request->validate([
            'location' => ['required', 'string', 'max:255'],
            'wound_type' => ['required', 'string', 'max:255'],
            'size' => ['nullable', 'string', 'max:255'],
            'appearance' => ['nullable', 'string', 'max:255'],
            'dressing' => ['nullable', 'string', 'max:255'],
            'intervention' => ['nullable', 'string'],
        ]);

        $wound = $this->wounds->record($episode, $validated, $request->user());

        if ($request->hasFile('image')) {
            $this->wounds->attachImage($wound, $request->file('image'), $request->user());
        }

        $this->auditLogger->log('CREATE', NursingWoundAssessment::class, $wound->id, null, $wound->toArray(), $request);

        return back()->with('success', 'Wound assessment recorded.');
    }

    public function storeEducation(Request $request, NursingEpisode $episode)
    {
        Gate::authorize('nursing.education.create');

        $validated = $request->validate([
            'topic' => ['required', 'string', 'max:255'],
            'education_provided' => ['required', 'string'],
            'method' => ['nullable', 'string', 'max:255'],
            'patient_understanding' => ['nullable', 'string', 'max:255'],
            'caregiver_involvement' => ['nullable', 'boolean'],
        ]);

        $education = $this->education->record($episode, $validated, $request->user());

        $this->auditLogger->log('CREATE', \App\Models\Nursing\NursingEducation::class, $education->id, null, $education->toArray(), $request);

        return back()->with('success', 'Patient education recorded.');
    }
}
