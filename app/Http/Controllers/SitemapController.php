<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $urls = [
            [
                'loc' => route('home'),
                'changefreq' => 'weekly',
                'priority' => '1.0',
            ],
            [
                'loc' => route('guide'),
                'changefreq' => 'monthly',
                'priority' => '0.8',
            ],
            [
                'loc' => route('developer-guide'),
                'changefreq' => 'monthly',
                'priority' => '0.6',
            ],
            [
                'loc' => route('terms'),
                'changefreq' => 'yearly',
                'priority' => '0.3',
            ],
            [
                'loc' => route('privacy'),
                'changefreq' => 'yearly',
                'priority' => '0.3',
            ],
        ];

        $xml = view('sitemap', [
            'urls' => $urls,
            'lastmod' => now()->toDateString(),
        ])->render();

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
