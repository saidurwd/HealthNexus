<?php

namespace App\Http\Controllers;

use App\Jobs\Import\CommitImportBatch;
use App\Jobs\Import\ValidateImportBatch;
use App\Models\ImportBatch;
use App\Services\Import\ImportContext;
use App\Services\Import\ImportRegistry;
use App\Services\Import\ImportService;
use App\Services\TenantContextResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportController extends Controller
{
    public function __construct(
        private readonly ImportService $imports,
        private readonly ImportRegistry $registry,
        private readonly TenantContextResolver $tenant,
    ) {}

    public function index(Request $request): View
    {
        abort_unless($request->user()->can('import.view'), 403);

        $allowed = collect($this->registry->all())->filter(fn ($d) => $request->user()->can($d->permission()))->values();

        return view('admin.imports.index', [
            'definitions' => $allowed,
            'batches' => ImportBatch::query()->where('organization_id', $this->tenant->getOrganizationId())->latest('id')->limit(30)->get(),
        ]);
    }

    public function template(string $key): StreamedResponse
    {
        abort_unless(request()->user()->can($this->registry->find($key)->permission()), 403);

        return $this->imports->template($key);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'import' => ['required', 'string', 'max:40'],
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:'.config('imports.max_file_kb')],
        ]);

        $context = new ImportContext($this->tenant->getOrganizationId(), $this->tenant->getBranchId(), $request->user());
        $batch = $this->imports->upload($request->user(), $context, $validated['import'], $request->file('file')->getRealPath(), $request->file('file')->getClientOriginalName());

        ValidateImportBatch::dispatch($batch->id);

        return redirect()->route('admin.imports.show', $batch)->with('success', __('File uploaded. Checking the rows now.'));
    }

    public function show(Request $request, ImportBatch $batch): View
    {
        $this->assertVisible($request, $batch);

        return view('admin.imports.show', [
            'batch' => $batch,
            'definition' => $this->registry->find($batch->import_key),
            'rows' => $batch->rows()->orderByRaw("case status when 'error' then 0 when 'duplicate' then 1 else 2 end")->orderBy('row_number')->limit(config('imports.preview_rows'))->get(),
        ]);
    }

    public function commit(Request $request, ImportBatch $batch): RedirectResponse
    {
        $this->assertVisible($request, $batch);
        abort_unless($request->user()->can($this->registry->find($batch->import_key)->permission()), 403);
        abort_unless($batch->status === 'validated', 422, 'Only a validated batch can be imported.');

        CommitImportBatch::dispatch($batch->id, $request->user()->id);

        return back()->with('success', __('Import started. Refresh this page to see progress.'));
    }

    public function rollback(Request $request, ImportBatch $batch): RedirectResponse
    {
        $this->assertVisible($request, $batch);
        $this->imports->rollback($batch, $request->user());

        return back()->with('success', __('Import rolled back.'));
    }

    public function errors(Request $request, ImportBatch $batch): StreamedResponse
    {
        $this->assertVisible($request, $batch);

        return $this->imports->errorFile($batch);
    }

    /** Another organization's batch does not exist for this caller (404). */
    private function assertVisible(Request $request, ImportBatch $batch): void
    {
        abort_unless($batch->organization_id === $this->tenant->getOrganizationId(), 404);
        abort_unless($request->user()->can('import.view'), 403);
    }
}
