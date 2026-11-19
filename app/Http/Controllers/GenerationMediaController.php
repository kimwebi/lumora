<?php

namespace App\Http\Controllers;

use App\Models\Generation;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Serves a render that is stored in the database. Used on hosts without a
 * persistent filesystem, where /storage/generations cannot be relied on.
 */
class GenerationMediaController extends Controller
{
    public function __invoke(Request $request, Generation $generation): Response
    {
        $image = $generation->image;

        abort_if($image === null, 404);

        $etag = '"'.$image->checksum.'"';

        if ($request->headers->get('If-None-Match') === $etag) {
            return response('', 304, [
                'ETag' => $etag,
                'Cache-Control' => 'public, max-age=604800',
            ]);
        }

        $binary = base64_decode($image->data, true);

        abort_if($binary === false || $binary === '', 404);

        return response($binary, 200, [
            'Content-Type' => $image->mime,
            'Content-Length' => (string) strlen($binary),
            'Cache-Control' => 'public, max-age=604800',
            'ETag' => $etag,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
