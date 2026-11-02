<?php

namespace Modules\Ai\Http\Controllers\Web;

use App\Models\Ai\AiOutput;
use App\Models\Encounter;
use App\Models\Patient;
use App\Services\Ai\AiBlocked;
use App\Services\Ai\OutputService;
use App\Services\Ai\UseCases\InventoryForecast;
use App\Services\Ai\UseCases\KnowledgeService;
use App\Services\Ai\UseCases\NlAnalytics;
use App\Services\Ai\UseCases\NoteDraft;
use App\Services\Ai\UseCases\TimelineSummary;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Asking the assistant (every answer is a draft in the outputs list) and reviewing what it drafted. */
class AiAssistController extends AiController
{
    public function __construct(TenantContextResolver $tenant, private readonly TimelineSummary $summaries, private readonly NoteDraft $notes, private readonly KnowledgeService $knowledge, private readonly NlAnalytics $figures,
        private readonly InventoryForecast $forecasts, private readonly OutputService $outputs)
    {
        parent::__construct($tenant);
    }

    private function go(callable $call)
    {
        try {
            $o = $call();
        } catch (AiBlocked $e) {
            return back()->withInput()->with('warning', $e->getMessage());
        }

        return redirect()->route('admin.ai.outputs.show', $o->public_id);
    }

    public function summary(Request $request)
    {
        $this->allow($request, 'ai.use');

        return view('admin.ai.ask', ['mode' => 'summary', 'title' => __('Read a patient\'s record back'), 'help' => __('Type the patient number. The assistant lists what is in the record, with where each line came from. It adds nothing.')]);
    }

    public function runSummary(Request $request)
    {
        $this->allow($request, 'ai.use');
        $d = $request->validate(['patient_no' => ['required', 'string', 'max:40'], 'days' => ['nullable', 'integer', 'min:7', 'max:3650']]);
        $patient = Patient::query()->where('organization_id', $this->organizationId())->where('enterprise_patient_no', trim($d['patient_no']))->first() ?? throw ValidationException::withMessages(['patient_no' => __('No patient has that number.')]);

        return $this->go(fn () => $this->summaries->summarise($request->user(), $patient, isset($d['days']) ? (int) $d['days'] : null));
    }

    public function notes(Request $request)
    {
        $this->allow($request, 'ai.use');

        return view('admin.ai.ask', ['mode' => 'notes', 'title' => __('Draft a note for a visit'), 'help' => __('Type the visit number. You get the facts of the visit laid out as a draft. The assessment and plan are yours to write.')]);
    }

    public function runNote(Request $request)
    {
        $this->allow($request, 'ai.use');
        $d = $request->validate(['encounter_no' => ['required', 'string', 'max:60']]);
        $enc = Encounter::query()->where('organization_id', $this->organizationId())->where('encounter_no', trim($d['encounter_no']))->first() ?? throw ValidationException::withMessages(['encounter_no' => __('No visit has that number.')]);

        return $this->go(fn () => $this->notes->draft($request->user(), $enc));
    }

    public function ask(Request $request)
    {
        $this->allow($request, 'ai.use');

        return view('admin.ai.ask', ['mode' => 'ask', 'title' => __('Ask about hospital policy'), 'help' => __('Ask a question. The answer quotes the approved policies and procedures, with the document and version. If they do not cover it you are told so.')]);
    }

    public function runAsk(Request $request)
    {
        $this->allow($request, 'ai.use');
        $d = $request->validate(['question' => ['required', 'string', 'max:500']]);

        return $this->go(fn () => $this->knowledge->answer($request->user(), $d['question'], $this->organizationId()));
    }

    public function figures(Request $request)
    {
        $this->allow($request, 'ai.use');

        return view('admin.ai.ask', ['mode' => 'figures', 'title' => __('Ask about the hospital\'s figures'), 'help' => __('Ask in plain words, for example "net revenue by department last month". You see how the question was read, and the result is limited to what you are allowed to see.')]);
    }

    public function runFigures(Request $request)
    {
        $this->allow($request, 'ai.use');
        $d = $request->validate(['question' => ['required', 'string', 'max:300']]);

        return $this->go(fn () => $this->figures->ask($request->user(), $d['question'], $this->organizationId()));
    }

    public function forecast(Request $request)
    {
        $this->allow($request, 'ai.use');

        return view('admin.ai.ask', ['mode' => 'forecast', 'title' => __('Forecast the use of an item'), 'help' => __('Choose an item. The forecast shows the range and how wrong the same method was on recent days. It is advice only: nothing is ordered from it.'),
            'items' => DB::table('inventory_items')->where('organization_id', $this->organizationId())->where('status', 'active')->orderBy('name')->limit(300)->get(['id', 'code', 'name'])]);
    }

    public function runForecast(Request $request)
    {
        $this->allow($request, 'ai.use');
        $d = $request->validate(['item_id' => ['required', 'integer']]);

        return $this->go(fn () => $this->forecasts->forecast($request->user(), (int) $d['item_id'], $this->organizationId()));
    }

    public function outputs(Request $request)
    {
        $this->allow($request, 'ai.use', 'ai.output.view_all');
        $all = $request->user()->can('ai.output.view_all') && $request->query('show', 'mine') === 'all';
        $status = $request->query('status', 'draft');
        $q = AiOutput::query()->where('organization_id', $this->organizationId())->when(! $all, fn ($w) => $w->where('requested_by', $request->user()->id))->when($status !== 'all', fn ($w) => $w->where('status', $status))->orderByDesc('id');

        return view('admin.ai.outputs', ['outputs' => $q->paginate(25)->withQueryString(), 'status' => $status, 'all' => $all, 'canAll' => $request->user()->can('ai.output.view_all')]);
    }

    public function show(Request $request, string $output)
    {
        $o = $this->find(AiOutput::class, $output);
        abort_unless($o->requested_by === $request->user()->id || $request->user()->can('ai.output.view_all') || $request->user()->can('ai.output.review'), 403);
        $patient = $o->patient_id ? Patient::query()->find($o->patient_id) : null;

        return view('admin.ai.output', ['o' => $o->load('request', 'reviewer', 'feedback'), 'patient' => $patient, 'canReview' => $request->user()->can('ai.output.review') && $o->status === 'draft' && $o->kind !== 'none']);
    }

    public function accept(Request $request, string $output)
    {
        $d = $request->validate(['patient_no' => ['nullable', 'string', 'max:40'], 'edited_text' => ['nullable', 'string', 'max:20000'], 'note' => ['nullable', 'string', 'max:500']]);
        $this->outputs->accept($this->find(AiOutput::class, $output), $request->user(), $d['patient_no'] ?? null, $d['edited_text'] ?? null, $d['note'] ?? null);

        return back()->with('success', __('Accepted. Nothing was written into the record: copy what you want into it yourself.'));
    }

    public function reject(Request $request, string $output)
    {
        $d = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $this->outputs->reject($this->find(AiOutput::class, $output), $request->user(), $d['reason']);

        return back()->with('success', __('Rejected.'));
    }

    public function feedback(Request $request, string $output)
    {
        $this->allow($request, 'ai.use');
        $d = $request->validate(['rating' => ['required', 'string', 'max:10'], 'error_type' => ['nullable', 'string', 'max:14'], 'comment' => ['nullable', 'string', 'max:500']]);
        $o = $this->find(AiOutput::class, $output);
        abort_unless($o->requested_by === $request->user()->id || $request->user()->can('ai.output.review'), 403);
        $this->outputs->feedback($o, $request->user(), $d['rating'], $d['error_type'] ?? null, $d['comment'] ?? null);

        return back()->with('success', __('Thank you. Your feedback was recorded.'));
    }
}
