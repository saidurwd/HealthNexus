<?php

namespace Modules\Quality\Http\Controllers\Web;

use App\Models\Quality\QaAction;
use App\Models\Quality\QaIncident;
use App\Services\Quality\ActionService;
use App\Services\Quality\QualityScope;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/** Corrective and preventive actions. Anyone can see and finish the actions that are theirs; the quality team sees all of them. */
class ActionController extends QualityController
{
    public function __construct(TenantContextResolver $tenant, private readonly ActionService $actions, private readonly QualityScope $scope)
    {
        parent::__construct($tenant);
    }

    public function index(Request $request)
    {
        $this->allow($request, 'quality.incident.report', 'quality.action.view');
        $view = $request->query('show', $request->user()->can('quality.action.view') ? 'all' : 'mine');
        $all = $request->user()->can('quality.action.view') && $view !== 'mine';
        $query = $all ? $this->scope->apply(QaAction::query(), $request->user(), $this->organizationId()) : QaAction::query()->where('organization_id', $this->organizationId())->where('owner_id', $request->user()->id);
        $query->with('owner')->when($view === 'overdue', fn ($q) => $q->whereIn('status', ['open', 'in_progress'])->where('due_on', '<', today()->toDateString()))->when($view === 'verify', fn ($q) => $q->where('status', 'done'))
            ->when(in_array($view, ['open']), fn ($q) => $q->whereIn('status', ['open', 'in_progress']))->orderByRaw("case status when 'verified' then 1 when 'cancelled' then 1 else 0 end")->orderBy('due_on');

        return view('admin.quality.actions', ['actions' => $query->paginate(25)->withQueryString(), 'view' => $view, 'staff' => $request->user()->can('quality.action.manage') ? $this->staff() : collect()]);
    }

    public function store(Request $request)
    {
        $this->allow($request, 'quality.action.manage');
        $d = $request->validate(['incident' => ['nullable', 'string', 'max:26'], 'action_type' => ['required', 'string', 'max:12'], 'description' => ['required', 'string', 'max:1000'], 'owner_id' => ['required', 'integer'], 'due_on' => ['nullable', 'date']]);
        $source = null;
        $branch = $this->branchId();
        if (! empty($d['incident'])) {
            $source = $this->find(QaIncident::class, $d['incident']);
            $branch = $source->branch_id;
        }
        $this->actions->create($this->organizationId(), ['source_type' => $source ? 'incident' : null, 'source_id' => $source ? $source->id : null, 'action_type' => $d['action_type'], 'description' => $d['description'], 'owner_id' => $d['owner_id'], 'due_on' => $d['due_on'] ?? null, 'branch_id' => $branch], $request->user());

        return back()->with('success', __('The action was added.'));
    }

    public function start(Request $request, string $action)
    {
        $this->actions->start($this->mine($request, $action), $request->user());

        return back()->with('success', __('Saved.'));
    }

    public function complete(Request $request, string $action)
    {
        $d = $request->validate(['note' => ['required', 'string', 'max:1000']]);
        $this->actions->complete($this->mine($request, $action), $request->user(), $d['note']);

        return back()->with('success', __('Saved. A second person will check it.'));
    }

    public function verify(Request $request, string $action)
    {
        $this->allow($request, 'quality.action.verify');
        $d = $request->validate(['effective' => ['required', 'in:yes,no'], 'note' => ['nullable', 'string', 'max:500']]);
        $this->actions->verify($this->find(QaAction::class, $action), $request->user(), $d['effective'] === 'yes', $d['note'] ?? null);

        return back()->with('success', __('Saved.'));
    }

    public function cancel(Request $request, string $action)
    {
        $this->allow($request, 'quality.action.manage');
        $d = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $this->actions->cancel($this->find(QaAction::class, $action), $request->user(), $d['reason']);

        return back()->with('success', __('The action was cancelled.'));
    }

    /** The owner, or a person who manages actions. */
    private function mine(Request $request, string $publicId): QaAction
    {
        $a = $this->find(QaAction::class, $publicId);
        if ($a->owner_id !== $request->user()->id && ! $request->user()->can('quality.action.manage')) {
            throw ValidationException::withMessages(['action' => __('This action belongs to someone else.')]);
        }

        return $a;
    }
}
