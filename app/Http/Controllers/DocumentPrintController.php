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
            'format' => ['nullable', 'in:html,pdf'],
        ]);

        $language = $validated['lang'] ?? (app()->getLocale() === 'bn' ? 'bn' : 'en');

        if (($validated['format'] ?? 'html') === 'pdf') {
            return response($documents->renderPdf($type, $reference, $language, $request->user(), $validated['reason'] ?? null))
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="'.preg_replace('/[^A-Za-z0-9._-]/', '_', $type.'-'.$reference).'.pdf"')
                ->header('Cache-Control', 'no-store, private');
        }

        return response($documents->render($type, $reference, $language, $request->user(), $validated['reason'] ?? null))
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Cache-Control', 'no-store, private');
    }
}
