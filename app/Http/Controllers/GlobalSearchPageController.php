<?php

namespace App\Http\Controllers;

use App\Services\Search\GlobalSearchService;
use App\Services\Search\ScanResolver;
use Illuminate\Http\Request;

/** Web face of the top-bar search: a scanned code that names one record goes straight to it; otherwise show the matches. */
class GlobalSearchPageController extends Controller
{
    public function __invoke(Request $request, GlobalSearchService $search, ScanResolver $scan)
    {
        $term = trim((string) $request->query('q', ''));

        if ($term !== '' && ($hit = $scan->resolve($request->user(), $term)) && $hit['url']) {
            return redirect($hit['url']);
        }

        return view('admin.search.index', ['term' => $term, 'results' => $term === '' ? collect() : $search->search($request->user(), $term, 30)]);
    }
}
