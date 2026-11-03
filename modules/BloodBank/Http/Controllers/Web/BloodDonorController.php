<?php

namespace Modules\BloodBank\Http\Controllers\Web;

use App\Models\Blood\BloodDonation;
use App\Models\Blood\BloodDonor;
use App\Models\Blood\BloodDonorAdverseEvent;
use App\Models\Blood\BloodDonorScreening;
use App\Models\Blood\BloodUnit;
use App\Models\Branch;
use App\Services\AuditLogger;
use App\Services\Blood\BloodDonationService;
use App\Services\Blood\BloodDonorService;
use App\Services\Blood\BloodPolicy;
use App\Services\Blood\BloodProcessingService;
use App\Services\Blood\BloodTestingService;
use App\Services\TenantContextResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BloodDonorController extends BloodController
{
    public function __construct(TenantContextResolver $tenant, private readonly BloodDonorService $donors, private readonly BloodDonationService $donations, private readonly AuditLogger $audit)
    {
        parent::__construct($tenant);
    }

    public function index(Request $request)
    {
        $this->allow($request, 'donor.view');
        $term = trim((string) $request->query('q'));
        $donors = BloodDonor::query()->with('patient:id,first_name,last_name,phone,sex')->where('organization_id', $this->organizationId())
            ->when($term !== '', fn ($q) => $q->where(fn ($w) => $w->where('donor_number', 'like', "%$term%")->orWhereHas('patient', fn ($p) => $p->where('first_name', 'like', "%$term%")->orWhere('last_name', 'like', "%$term%")->orWhere('phone', 'like', "%$term%"))))
            ->latest('id')->paginate(25)->withQueryString();

        return view('admin.blood.donors.index', ['donors' => $donors, 'q' => $term]);
    }

    public function create(Request $request)
    {
        $this->allow($request, 'donor.create');

        return view('admin.blood.donors.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->allow($request, 'donor.create');
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'], 'last_name' => ['required', 'string', 'max:100'], 'sex' => ['required', 'in:M,F,O'],
            'date_of_birth' => ['required', 'date', 'before:today'], 'phone' => ['nullable', 'string', 'max:30'], 'replacement' => ['sometimes', 'boolean'], 'consent' => ['accepted'],
        ]);
        $branch = Branch::query()->where('organization_id', $this->organizationId())->findOrFail($this->tenant->getBranchId());
        $donor = $this->donors->register($request->user(), $branch, collect($validated)->only(['first_name', 'last_name', 'sex', 'date_of_birth', 'phone'])->all(), (bool) ($validated['replacement'] ?? false));
        $this->donors->recordConsent($donor, $request->user());
        $this->audit->log('BLOOD_DONOR_REGISTERED', BloodDonor::class, $donor->id, null, ['donor_number' => $donor->donor_number], $request);

        return redirect()->route('admin.blood.donors.show', $donor)->with('success', __('Donor registered.'));
    }

    public function show(Request $request, BloodDonor $donor)
    {
        $this->scoped($request, $donor, 'donor.view');

        return view('admin.blood.donors.show', [
            'donor' => $donor->load('patient'), 'screenings' => $donor->hasMany(BloodDonorScreening::class, 'donor_id')->latest('screened_at')->limit(10)->get(),
            'donations' => $donor->donations()->latest('started_at')->limit(10)->get(), 'deferralReasons' => array_keys(app(BloodPolicy::class)->get('deferral_reasons')),
        ]);
    }

    public function screen(Request $request, BloodDonor $donor): RedirectResponse
    {
        $this->scoped($request, $donor, 'screening.perform');
        $validated = $request->validate([
            'weight_kg' => ['nullable', 'numeric', 'between:20,250'], 'hemoglobin' => ['nullable', 'numeric', 'between:3,25'], 'systolic' => ['nullable', 'integer', 'between:50,260'],
            'diastolic' => ['nullable', 'integer', 'between:30,160'], 'pulse' => ['nullable', 'integer', 'between:30,220'], 'temperature_c' => ['nullable', 'numeric', 'between:33,43'],
            'questionnaire' => ['nullable', 'array'], 'questionnaire.*' => ['boolean'],
        ]);
        $screening = $this->donors->screen($donor, $request->user(), $validated);

        return back()->with($screening->outcome === 'eligible' ? 'success' : 'warning', $screening->outcome === 'eligible' ? __('Eligible to donate.') : __('Deferred — not eligible to donate now.'));
    }

    public function startDonation(Request $request, BloodDonor $donor): RedirectResponse
    {
        $this->scoped($request, $donor, 'donation.collect');
        $validated = $request->validate(['screening' => ['required', 'string', 'size:26'], 'bag_lot' => ['nullable', 'string', 'max:40']]);
        $screening = BloodDonorScreening::query()->where('organization_id', $donor->organization_id)->where('donor_id', $donor->id)->where('public_id', $validated['screening'])->firstOrFail();
        $branch = Branch::query()->where('organization_id', $this->organizationId())->findOrFail($this->tenant->getBranchId());
        $donation = $this->donations->start($donor, $screening, $branch, $request->user(), $validated['bag_lot'] ?? null);

        return redirect()->route('admin.blood.donations.show', $donation)->with('success', __('Donation started.'));
    }

    public function showDonation(Request $request, BloodDonation $donation)
    {
        $this->scoped($request, $donation, 'donation.view');

        return view('admin.blood.donations.show', [
            'donation' => $donation->load(['donor.patient', 'tests']), 'units' => BloodUnit::query()->where('donation_id', $donation->id)->orderBy('id')->get(),
            'adverseTypes' => app(BloodPolicy::class)->get('adverse_event_types'),
            'adverseEvents' => BloodDonorAdverseEvent::query()->where('donation_id', $donation->id)->latest('occurred_at')->get(),
        ]);
    }

    public function completeDonation(Request $request, BloodDonation $donation): RedirectResponse
    {
        $this->scoped($request, $donation, 'donation.collect');
        $validated = $request->validate(['volume_ml' => ['required', 'integer', 'between:100,600']]);
        $this->donations->complete($donation, (int) $validated['volume_ml'], $request->user());

        return back()->with('success', __('Collection recorded. The bag is in quarantine and the tests are ordered.'));
    }

    public function abortDonation(Request $request, BloodDonation $donation): RedirectResponse
    {
        $this->scoped($request, $donation, 'donation.collect');
        $validated = $request->validate(['reason' => ['required', 'string', 'max:200']]);
        $this->donations->abort($donation, $validated['reason']);

        return back()->with('success', __('Donation stopped.'));
    }

    public function adverseEvent(Request $request, BloodDonation $donation): RedirectResponse
    {
        $this->scoped($request, $donation, 'donation.collect');
        $validated = $request->validate(['event_type' => ['required', 'string'], 'severity' => ['required', 'in:mild,moderate,severe'], 'description' => ['nullable', 'string', 'max:500'], 'action_taken' => ['nullable', 'string', 'max:500']]);
        $this->donations->recordAdverseEvent($donation, $request->user(), $validated['event_type'], $validated['severity'], $validated['description'] ?? null, $validated['action_taken'] ?? null);

        return back()->with('success', __('Donor reaction recorded.'));
    }

    public function finishObservation(Request $request, BloodDonation $donation): RedirectResponse
    {
        $this->scoped($request, $donation, 'donation.collect');
        $this->donations->finishObservation($donation);

        return back()->with('success', __('Rest period finished.'));
    }

    public function separate(Request $request, BloodDonation $donation, BloodProcessingService $processing): RedirectResponse
    {
        $this->scoped($request, $donation, 'unit.process');
        $whole = BloodUnit::query()->where('donation_id', $donation->id)->where('component', 'whole_blood')->firstOrFail();
        $processing->separate($whole, $request->user());

        return back()->with('success', __('Components made.'));
    }

    public function syncTests(Request $request, BloodDonation $donation, BloodTestingService $testing): RedirectResponse
    {
        $this->scoped($request, $donation, 'test.record');
        $n = $testing->syncFromLaboratory($donation);

        return back()->with('success', trans_choice(':n result read from the laboratory.|:n results read from the laboratory.', $n, ['n' => $n]));
    }

    public function release(Request $request, BloodDonation $donation, BloodTestingService $testing): RedirectResponse
    {
        $this->scoped($request, $donation, 'unit.release');
        $n = $testing->release($donation, $request->user());
        $this->audit->log('BLOOD_RELEASE', BloodDonation::class, $donation->id, null, ['units' => $n], $request);

        return back()->with('success', __(':n unit(s) released to the shelf.', ['n' => $n]));
    }
}
