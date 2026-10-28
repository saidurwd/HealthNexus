<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Low-bandwidth support for the mobile API (guideline override 6): a GET carries an ETag and a repeat with If-None-Match gets an empty 304 instead of the body. */
class PortalEtag
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        if (! $request->isMethod('GET') || $response->getStatusCode() !== 200 || $response->headers->has('ETag')) {
            return $response;
        }
        $etag = '"'.md5((string) $response->getContent()).'"';
        $response->headers->set('ETag', $etag);
        $response->headers->set('Cache-Control', 'private, no-cache');
        if (in_array($etag, array_map('trim', explode(',', (string) $request->header('If-None-Match'))), true)) {
            $response->setNotModified();
        }

        return $response;
    }
}
