<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Marks unlisted pages as non-indexable.
 *
 * This does not restrict access: the page is reachable by anyone who knows the
 * URL. The header (with the noindex meta tag) keeps it out of search indexes.
 */
class PreventSearchIndexing
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');

        return $response;
    }
}
