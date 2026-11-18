<?php

namespace App\Support;

/**
 * Server-rendered SEO metadata. These values are written into the root Blade
 * document so crawlers and social scrapers receive meaningful tags before
 * JavaScript runs. The Vue application updates the same tags on navigation.
 */
final class Seo
{
    public static function forPath(string $path, array $overrides = []): array
    {
        $path = '/'.ltrim($path, '/');

        [$title, $description, $indexable] = match (true) {
            $path === '/' => [
                'Lumora — AI Image Generation, Beautifully Rendered',
                'Turn imagination into gallery-quality AI art. Generate, refine and download stunning images with transparent pricing and a studio built for creators.',
                true,
            ],
            $path === '/login' => [
                'Sign in — Lumora',
                'Welcome back to your Lumora studio. Sign in to generate AI images, manage credits and revisit your creations.',
                false,
            ],
            $path === '/register' => [
                'Create your Lumora account',
                'Join Lumora and start creating gallery-quality AI imagery in seconds. New accounts include complimentary credits.',
                false,
            ],
            $path === '/terms' => [
                'Terms of Service — Lumora',
                'The terms that govern your use of the Lumora AI image generation studio, including credits, pricing, failed renders and content ownership.',
                true,
            ],
            $path === '/privacy' => [
                'Privacy Policy — Lumora',
                'How Lumora collects, uses and protects your data, prompts, reference images and credit ledger.',
                true,
            ],
            $path === '/guide' => [
                'User Guide — Lumora',
                'A friendly, step-by-step guide to creating AI images, understanding credits, managing your gallery and personalising your Lumora studio.',
                true,
            ],
            $path === '/developer-guide' => [
                'Developer Guide — Lumora',
                'Lumora architecture, local setup, Pixelforge API keys, webhook signatures and the generation lifecycle for developers.',
                true,
            ],
            default => [
                'Lumora Studio',
                'Your Lumora workspace for AI image generation, credits and creative history.',
                false,
            ],
        };

        $base = rtrim((string) config('app.url'), '/');
        $canonical = $base.$path;

        return array_merge([
            'title' => $title,
            'description' => $description,
            'canonical' => $canonical,
            'robots' => $indexable ? 'index, follow, max-image-preview:large' : 'noindex, nofollow',
            'og_type' => 'website',
            'og_image' => $base.'/images/og-cover.png',
            'site_name' => config('lumora.brand.name'),
            'twitter' => config('lumora.seo.twitter'),
            'json_ld' => $indexable ? self::jsonLd($base) : null,
        ], $overrides);
    }

    private static function jsonLd(string $base): array
    {
        return [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'Organization',
                    '@id' => $base.'/#organization',
                    'name' => 'Lumora',
                    'url' => $base,
                    'logo' => $base.'/favicon.svg',
                    'sameAs' => [],
                ],
                [
                    '@type' => 'WebApplication',
                    '@id' => $base.'/#app',
                    'name' => 'Lumora',
                    'url' => $base,
                    'applicationCategory' => 'DesignApplication',
                    'operatingSystem' => 'Web',
                    'description' => 'AI image generation studio with transparent pricing, live generation progress and a creative history gallery.',
                    'offers' => [
                        '@type' => 'Offer',
                        'price' => '0',
                        'priceCurrency' => 'USD',
                        'description' => 'New accounts receive complimentary credits.',
                    ],
                ],
            ],
        ];
    }
}
