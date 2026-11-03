<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Services\Documents\Letterhead;
use App\Services\Ops\TrainingMode;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Storage;

class DowntimeController extends Controller
{
    public function __construct(private readonly TenantContextResolver $tenant) {}

    public function index(Request $request)
    {
        abort_unless($request->user()->can('downtime.view'), 403);

        return view('admin.downtime.index', [
            'forms' => collect(config('downtime_forms'))->map(fn ($f) => $f['title']),
            'latest' => DB::table('downtime_censuses')->where('organization_id', $this->tenant->getOrganizationId())->latest('generated_at')->first(),
        ]);
    }

    /** A blank paper form to print before it is needed. ?lang=en|bn|en+bn */
    public function form(Request $request, string $form)
    {
        abort_unless($request->user()->can('downtime.view'), 403);
        $definition = config("downtime_forms.$form") ?? abort(404);
        $language = in_array($request->query('lang'), ['en', 'bn', 'en+bn'], true) ? $request->query('lang') : 'en+bn';

        $label = function (string $text) use ($language): string {
            $bn = Lang::get($text, [], 'bn');

            return match ($language) {
                'bn' => $bn,
                'en+bn' => $bn === $text ? $text : $bn.' / '.$text,
                default => $text,
            };
        };

        return view('admin.downtime.form', [
            'form' => $definition, 'label' => $label, 'training' => app(TrainingMode::class)->active(),
            'letterhead' => app(Letterhead::class)->for($this->tenant->getBranchId() ? Branch::find($this->tenant->getBranchId()) : null),
        ]);
    }

    /** The newest stored census, served as-is (it is a self-contained HTML file). */
    public function census(Request $request)
    {
        abort_unless($request->user()->can('downtime.view'), 403);

        $latest = DB::table('downtime_censuses')->where('organization_id', $this->tenant->getOrganizationId())->latest('generated_at')->first();
        abort_unless($latest && Storage::disk($latest->disk)->exists($latest->path), 404, 'No census has been generated yet.');

        return response(Storage::disk($latest->disk)->get($latest->path))->header('Content-Type', 'text/html; charset=UTF-8')->header('Cache-Control', 'no-store, private');
    }
}
