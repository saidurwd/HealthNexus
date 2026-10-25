<?php

namespace App\Http\Controllers;

use App\Services\Documents\DocumentService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DocumentPrintController extends Controller
{
    public function __invoke(Request $request, DocumentService $documents, string $type, string $reference): Response
    {
        $validated = $request->validate([
            'lang' => ['nullable', 'string', 'max:8'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $language = $validated['lang'] ?? (app()->getLocale() === 'bn' ? 'bn' : 'en');

        return response($documents->render($type, $reference, $language, $request->user(), $validated['reason'] ?? null))
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Cache-Control', 'no-store, private');
    }
}
