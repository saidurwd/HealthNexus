<?php

namespace Modules\Files\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Services\FileService;
use Illuminate\Http\Request;

class FileController extends Controller
{
    public function __construct(private FileService $files) {}

    public function index(Request $request)
    {
        $files = File::query()
            ->where('uploaded_by', $request->user()->id)
            ->when($request->filled('search'), fn ($q, $search) => $q->where('original_name', 'like', "%{$search}%"))
            ->latest()
            ->paginate(20)
            ->appends($request->except('page'));

        return view('admin.files.index', compact('files'));
    }

    public function show(Request $request, File $file)
    {
        $this->authorize('view', $file);

        return view('admin.files.show', compact('file'));
    }

    public function download(Request $request, File $file)
    {
        $this->authorize('view', $file);

        return $this->files->download($file);
    }

    public function destroy(Request $request, File $file)
    {
        $this->authorize('manage', $file);

        $this->files->delete($file);

        return back()->with('success', 'File deleted successfully.');
    }
}
