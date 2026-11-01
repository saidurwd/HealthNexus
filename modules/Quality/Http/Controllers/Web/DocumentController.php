<?php

namespace Modules\Quality\Http\Controllers\Web;

use App\Models\Department;
use App\Models\Quality\QaAcknowledgement;
use App\Models\Quality\QaDocument;
use App\Models\Quality\QaDocumentVersion;
use App\Services\Quality\DocumentService;
use App\Services\Quality\QualityPolicy;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

/** Policies, procedures and protocols: read, acknowledge, draft, review, approve. */
class DocumentController extends QualityController
{
    public function __construct(TenantContextResolver $tenant, private readonly DocumentService $documents, private readonly QualityPolicy $policy)
    {
        parent::__construct($tenant);
    }

    public function index(Request $request)
    {
        $this->allow($request, 'quality.document.view');
        $category = $request->query('category');
        $docs = QaDocument::query()->with('current', 'owner')->where('organization_id', $this->organizationId())->when($category, fn ($q, $c) => $q->where('category', $c))
            ->when($request->query('q'), fn ($q, $t) => $q->where(fn ($w) => $w->where('code', 'like', '%'.$t.'%')->orWhere('title', 'like', '%'.$t.'%')))->orderBy('code')->paginate(30)->withQueryString();

        return view('admin.quality.documents.index', ['docs' => $docs, 'categories' => $this->policy->list('document.categories'), 'category' => $category, 'canManage' => $request->user()->can('quality.document.manage'), 'canApprove' => $request->user()->can('quality.document.approve')]);
    }

    public function toRead(Request $request)
    {
        $this->allow($request, 'quality.document.acknowledge');

        return view('admin.quality.documents.to-read', ['docs' => $this->documents->pendingFor($request->user(), $this->organizationId())]);
    }

    public function create(Request $request)
    {
        $this->allow($request, 'quality.document.manage');

        return view('admin.quality.documents.create', ['categories' => $this->policy->list('document.categories'), 'departments' => Department::query()->where('organization_id', $this->organizationId())->orderBy('name')->get(['id', 'name']), 'months' => $this->policy->int('document.default_review_months', 12)]);
    }

    public function store(Request $request)
    {
        $this->allow($request, 'quality.document.manage');
        $d = $request->validate(['code' => ['required', 'string', 'max:30'], 'title' => ['required', 'string', 'max:200'], 'category' => ['required', 'string', 'max:12'], 'department_id' => ['nullable', 'integer'], 'review_months' => ['nullable', 'integer', 'min:1', 'max:60'], 'body' => ['required', 'string', 'max:200000']]);
        $doc = $this->documents->create($this->organizationId(), $d, $request->user());

        return redirect()->route('admin.quality.documents.show', $doc->public_id)->with('success', __('The first version is a draft. Send it for review when it is ready.'));
    }

    public function show(Request $request, string $document)
    {
        $this->allow($request, 'quality.document.view');
        $doc = $this->find(QaDocument::class, $document);
        $doc->load('versions.author', 'versions.approver', 'assignments', 'current', 'owner');
        $manage = $request->user()->can('quality.document.manage');
        $mine = $doc->current_version_id && QaAcknowledgement::query()->where(['version_id' => $doc->current_version_id, 'user_id' => $request->user()->id])->exists();
        $asked = $doc->current_version_id && $this->documents->audience($doc)->contains($request->user()->id);

        return view('admin.quality.documents.show', ['doc' => $doc, 'manage' => $manage, 'approve' => $request->user()->can('quality.document.approve'), 'acknowledged' => $mine, 'asked' => $asked,
            'ack' => $manage || $request->user()->can('quality.document.approve') ? $this->documents->acknowledgement($doc) : null, 'roles' => Role::query()->orderBy('name')->pluck('name'),
            'departments' => Department::query()->where('organization_id', $this->organizationId())->orderBy('name')->get(['id', 'name']), 'staff' => $manage ? $this->staff() : collect(), 'me' => $request->user()->id]);
    }

    public function acknowledge(Request $request, string $document)
    {
        $this->allow($request, 'quality.document.acknowledge');
        $this->documents->acknowledge($this->find(QaDocument::class, $document), $request->user());

        return back()->with('success', __('Thank you. Your reading is recorded.'));
    }

    public function newVersion(Request $request, string $document)
    {
        $this->allow($request, 'quality.document.manage');
        $d = $request->validate(['body' => ['required', 'string', 'max:200000'], 'change_note' => ['required', 'string', 'max:500']]);
        $this->documents->newVersion($this->find(QaDocument::class, $document), $d['body'], $d['change_note'], $request->user());

        return back()->with('success', __('A new draft was made.'));
    }

    public function assign(Request $request, string $document)
    {
        $this->allow($request, 'quality.document.manage');
        $d = $request->validate(['audience' => ['required', 'in:all,role,department,user'], 'value' => ['nullable', 'string', 'max:80']]);
        $this->documents->assign($this->find(QaDocument::class, $document), $d['audience'], (string) ($d['value'] ?? ''));

        return back()->with('success', __('Saved.'));
    }

    public function retire(Request $request, string $document)
    {
        $this->allow($request, 'quality.document.manage');
        $d = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $this->documents->retire($this->find(QaDocument::class, $document), $request->user(), $d['reason']);

        return back()->with('success', __('The document is retired.'));
    }

    public function edit(Request $request, int $version)
    {
        $this->allow($request, 'quality.document.manage');
        $d = $request->validate(['body' => ['required', 'string', 'max:200000'], 'change_note' => ['nullable', 'string', 'max:500']]);
        $this->documents->edit($this->version($version), $d['body'], $d['change_note'] ?? null);

        return back()->with('success', __('Saved.'));
    }

    public function submit(Request $request, int $version)
    {
        $this->allow($request, 'quality.document.manage');
        $this->documents->submit($this->version($version), $request->user());

        return back()->with('success', __('Sent for review.'));
    }

    public function decide(Request $request, int $version)
    {
        $this->allow($request, 'quality.document.approve');
        $d = $request->validate(['decision' => ['required', 'in:approve,reject'], 'note' => ['nullable', 'string', 'max:500'], 'effective_on' => ['nullable', 'date']]);
        $this->documents->decide($this->version($version), $request->user(), $d['decision'] === 'approve', $d['note'] ?? null, $d['effective_on'] ?? null);

        return back()->with('success', __('Saved.'));
    }

    private function version(int $id): QaDocumentVersion
    {
        return QaDocumentVersion::query()->where('organization_id', $this->organizationId())->findOrFail($id);
    }
}
