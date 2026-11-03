<?php

namespace Modules\Ai\Http\Controllers\Web;

use App\Models\Ai\AiEvalRun;
use App\Models\Ai\AiEvalSet;
use App\Models\Ai\AiKnowledge;
use App\Models\Ai\AiModel;
use App\Models\Ai\AiPrompt;
use App\Models\Ai\AiProvider;
use App\Models\Ai\AiUseCase;
use App\Services\Ai\AiPolicy;
use App\Services\Ai\Evaluator;
use App\Services\Ai\PromptRegistry;
use App\Services\Ai\ProviderService;
use App\Services\Ai\UseCaseService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;

/** What may run: functions, providers and models, prompts, knowledge, evaluations. */
class AiGovernanceController extends AiController
{
    public function __construct(TenantContextResolver $tenant, private readonly UseCaseService $useCases, private readonly ProviderService $providers, private readonly Evaluator $evaluator, private readonly PromptRegistry $prompts, private readonly AiPolicy $policy)
    {
        parent::__construct($tenant);
    }

    public function useCases(Request $request)
    {
        $this->allow($request, 'ai.usecase.view');
        $org = $this->organizationId();

        return view('admin.ai.usecases', ['useCases' => AiUseCase::query()->with('provider', 'model', 'owner')->where('organization_id', $org)->orderByDesc('implemented')->orderBy('id')->get(), 'providers' => AiProvider::query()->with('models')->where('organization_id', $org)->get(), 'staff' => $this->staff(),
            'can' => ['manage' => $request->user()->can('ai.usecase.manage'), 'approve' => $request->user()->can('ai.usecase.approve'), 'kill' => $request->user()->can('ai.killswitch')], 'me' => $request->user()->id]);
    }

    public function setup(Request $request, string $useCase)
    {
        $this->allow($request, 'ai.usecase.manage');
        $d = $request->validate(['owner_id' => ['nullable', 'integer'], 'provider_id' => ['nullable', 'integer'], 'model_id' => ['nullable', 'integer']]);
        $org = $this->organizationId();
        $uc = $this->find(AiUseCase::class, $useCase);
        $owner = ! empty($d['owner_id']) ? $this->staff()->firstWhere('id', (int) $d['owner_id']) : null;
        $provider = ! empty($d['provider_id']) ? AiProvider::query()->where('organization_id', $org)->findOrFail($d['provider_id']) : null;
        $model = ! empty($d['model_id']) ? AiModel::query()->where('organization_id', $org)->findOrFail($d['model_id']) : null;
        $uc->registered_by ??= $request->user()->id;
        $uc->save();
        $this->useCases->setup($uc, $owner, $provider, $model);

        return back()->with('success', __('Saved.'));
    }

    public function enable(Request $request, string $useCase)
    {
        $this->allow($request, 'ai.usecase.approve');
        $this->useCases->enable($this->find(AiUseCase::class, $useCase), $request->user());

        return back()->with('success', __('The function is switched on.'));
    }

    public function pause(Request $request, string $useCase)
    {
        $this->allow($request, 'ai.killswitch');
        $d = $request->validate(['reason' => ['required', 'string', 'max:300']]);
        $this->useCases->pause($this->find(AiUseCase::class, $useCase), $request->user(), $d['reason']);

        return back()->with('success', __('The function is paused.'));
    }

    public function retire(Request $request, string $useCase)
    {
        $this->allow($request, 'ai.usecase.manage');
        $d = $request->validate(['reason' => ['required', 'string', 'max:300']]);
        $this->useCases->retire($this->find(AiUseCase::class, $useCase), $request->user(), $d['reason']);

        return back()->with('success', __('The function is retired.'));
    }

    public function providers(Request $request)
    {
        $this->allow($request, 'ai.provider.manage', 'ai.model.manage', 'ai.usecase.view');
        $org = $this->organizationId();

        return view('admin.ai.providers', ['providers' => AiProvider::query()->with(['models.runs' => fn ($q) => $q->latest('id')])->where('organization_id', $org)->get(), 'sets' => AiEvalSet::query()->where('organization_id', $org)->get(), 'kinds' => $this->policy->list('provider.kinds'),
            'locations' => $this->policy->list('provider.locations'), 'levels' => $this->policy->list('data_levels'), 'can' => ['provider' => $request->user()->can('ai.provider.manage'), 'legal' => $request->user()->can('ai.provider.legal'), 'model' => $request->user()->can('ai.model.manage'),
                'approve' => $request->user()->can('ai.model.approve'), 'eval' => $request->user()->can('ai.eval.run')]]);
    }

    public function addProvider(Request $request)
    {
        $this->allow($request, 'ai.provider.manage');
        $d = $request->validate(['name' => ['required', 'string', 'max:100'], 'kind' => ['required', 'string', 'max:8'], 'location' => ['required', 'string', 'max:14'], 'endpoint' => ['nullable', 'string', 'max:300'], 'default_model' => ['nullable', 'string', 'max:100'],
            'secret_env' => ['nullable', 'string', 'max:80'], 'max_level' => ['nullable', 'string', 'max:2'], 'purpose' => ['nullable', 'string', 'max:300']]);
        $this->providers->add($this->organizationId(), $d, $request->user());

        return back()->with('success', __('The provider was added. It is off until you switch it on.'));
    }

    public function toggleProvider(Request $request, string $provider)
    {
        $this->allow($request, 'ai.provider.manage');
        $p = $this->find(AiProvider::class, $provider);
        $this->providers->setEnabled($p, $p->status !== 'enabled', $request->user());

        return back()->with('success', __('Saved.'));
    }

    public function legal(Request $request, string $provider)
    {
        $this->allow($request, 'ai.provider.legal');
        $d = $request->validate(['legal_ref' => ['nullable', 'string', 'max:120'], 'revoke' => ['nullable', 'boolean']]);
        $p = $this->find(AiProvider::class, $provider);
        ! empty($d['revoke']) ? $this->providers->revokeIdentifiable($p, $request->user()) : $this->providers->approveIdentifiable($p, $request->user(), (string) ($d['legal_ref'] ?? ''));

        return back()->with('success', __('Saved.'));
    }

    public function addModel(Request $request, string $provider)
    {
        $this->allow($request, 'ai.model.manage');
        $d = $request->validate(['name' => ['required', 'string', 'max:100'], 'version' => ['required', 'string', 'max:40'], 'notes' => ['nullable', 'string', 'max:300']]);
        $this->providers->addModel($this->find(AiProvider::class, $provider), $d['name'], $d['version'], $d['notes'] ?? null);

        return back()->with('success', __('The model was registered as a candidate. It must pass an evaluation before it can be used.'));
    }

    public function evaluate(Request $request, int $model)
    {
        $this->allow($request, 'ai.eval.run');
        $d = $request->validate(['set_id' => ['required', 'integer']]);
        $org = $this->organizationId();
        $run = $this->evaluator->run(AiModel::query()->where('organization_id', $org)->findOrFail($model), AiEvalSet::query()->where('organization_id', $org)->findOrFail($d['set_id']), $request->user());

        return back()->with($run->passed ? 'success' : 'warning', $run->passed ? __('The evaluation passed (recall :r%).', ['r' => round(100 * (float) $run->recall)]) : __('The evaluation did not pass (recall :r%, :u unsupported line(s), :f leak(s)).', ['r' => round(100 * (float) $run->recall), 'u' => $run->unsupported, 'f' => $run->forbidden_hits]));
    }

    public function approveModel(Request $request, int $model)
    {
        $this->allow($request, 'ai.model.approve');
        $m = AiModel::query()->where('organization_id', $this->organizationId())->findOrFail($model);
        $run = AiEvalRun::query()->where('model_id', $m->id)->where('passed', true)->latest('id')->first();
        if (! $run) {
            return back()->with('warning', __('This model has not passed an evaluation, so it cannot be approved.'));
        }
        $this->evaluator->approve($m, $run, $request->user());

        return back()->with('success', __('The model is approved.'));
    }

    public function retireModel(Request $request, int $model)
    {
        $this->allow($request, 'ai.model.approve');
        $this->evaluator->retire(AiModel::query()->where('organization_id', $this->organizationId())->findOrFail($model), $request->user());

        return back()->with('success', __('The model is retired and the functions that used it are paused.'));
    }

    public function prompts(Request $request)
    {
        $this->allow($request, 'ai.prompt.manage', 'ai.usecase.view');

        return view('admin.ai.prompts', ['prompts' => AiPrompt::query()->where('organization_id', $this->organizationId())->orderBy('use_case_code')->orderByDesc('version')->get(), 'codes' => array_keys(PromptRegistry::DEFAULTS), 'defaults' => PromptRegistry::DEFAULTS, 'canManage' => $request->user()->can('ai.prompt.manage'), 'me' => $request->user()->id]);
    }

    public function draftPrompt(Request $request)
    {
        $this->allow($request, 'ai.prompt.manage');
        $d = $request->validate(['use_case_code' => ['required', 'string', 'max:40'], 'template' => ['required', 'string', 'max:4000']]);
        $this->prompts->draft($this->organizationId(), $d['use_case_code'], $d['template'], $request->user());

        return back()->with('success', __('The prompt was saved as a draft. A second person must activate it.'));
    }

    public function activatePrompt(Request $request, int $prompt)
    {
        $this->allow($request, 'ai.prompt.manage');
        $this->prompts->activate(AiPrompt::query()->where('organization_id', $this->organizationId())->findOrFail($prompt), $request->user());

        return back()->with('success', __('The prompt is active.'));
    }

    public function knowledge(Request $request)
    {
        $this->allow($request, 'ai.knowledge.manage', 'ai.knowledge.approve');

        return view('admin.ai.knowledge', ['items' => AiKnowledge::query()->where('organization_id', $this->organizationId())->orderByDesc('id')->get(), 'can' => ['manage' => $request->user()->can('ai.knowledge.manage'), 'approve' => $request->user()->can('ai.knowledge.approve')], 'me' => $request->user()->id]);
    }

    public function addKnowledge(Request $request)
    {
        $this->allow($request, 'ai.knowledge.manage');
        $d = $request->validate(['title' => ['required', 'string', 'max:200'], 'body' => ['required', 'string', 'min:20', 'max:20000']]);
        AiKnowledge::create(['organization_id' => $this->organizationId(), 'title' => $d['title'], 'body' => $d['body'], 'status' => 'draft', 'created_by' => $request->user()->id]);

        return back()->with('success', __('Saved as a draft. A second person must approve it before it is used.'));
    }

    public function approveKnowledge(Request $request, string $item)
    {
        $this->allow($request, 'ai.knowledge.approve');
        $k = $this->find(AiKnowledge::class, $item);
        if ($k->created_by === $request->user()->id) {
            return back()->with('warning', __('A second person must approve a knowledge item.'));
        }
        $k->update(['status' => 'approved', 'approved_by' => $request->user()->id, 'approved_at' => now()]);

        return back()->with('success', __('Approved.'));
    }

    public function retireKnowledge(Request $request, string $item)
    {
        $this->allow($request, 'ai.knowledge.manage');
        $this->find(AiKnowledge::class, $item)->update(['status' => 'retired']);

        return back()->with('success', __('Retired.'));
    }

    public function evaluations(Request $request)
    {
        $this->allow($request, 'ai.eval.view');
        $org = $this->organizationId();

        return view('admin.ai.evaluations', ['sets' => AiEvalSet::query()->withCount('cases')->where('organization_id', $org)->get(), 'runs' => AiEvalRun::query()->with('model.provider', 'set')->where('organization_id', $org)->orderByDesc('id')->limit(30)->get()]);
    }
}
