<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Brand
    |--------------------------------------------------------------------------
    */

    'brand' => [
        'name' => 'Lumora',
        'tagline' => 'Your imagination, beautifully rendered.',
        'support_email' => env('LUMORA_SUPPORT_EMAIL', 'hello@lumora.app'),
    ],

    /*
    |--------------------------------------------------------------------------
    | SEO defaults
    |--------------------------------------------------------------------------
    */

    'seo' => [
        'title' => 'Lumora — AI Image Generation, Beautifully Rendered',
        'description' => 'Lumora turns your imagination into gallery-quality AI art. Generate stunning images in seconds with transparent pricing, live progress and a premium creative studio.',
        'keywords' => 'AI image generation, AI art generator, text to image, Lumora, AI design studio',
        'twitter' => env('LUMORA_TWITTER_HANDLE', '@lumoraapp'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Money & credits
    |--------------------------------------------------------------------------
    |
    | All monetary values are stored as integer micro-dollars (1 USD = 1,000,000
    | micros). One Lumora credit is pegged to $0.01 (10,000 micros).
    |
    */

    'credits' => [
        'micros_per_credit' => 10_000,
        'welcome_bonus_micros' => (int) env('LUMORA_WELCOME_BONUS_MICROS', 5_000_000),

        'packages' => [
            'starter' => [
                'name' => 'Starter',
                'credits' => 500,
                'price_micros' => 5_000_000,
                'description' => 'A focused set for trying ideas and shipping a first project.',
                'popular' => false,
            ],
            'studio' => [
                'name' => 'Studio',
                'credits' => 1_200,
                'price_micros' => 10_000_000,
                'description' => 'The everyday pack for creators. Includes 200 bonus credits.',
                'popular' => true,
            ],
            'pro' => [
                'name' => 'Pro',
                'credits' => 3_000,
                'price_micros' => 25_000_000,
                'description' => 'High-volume studio output with the best value per render.',
                'popular' => false,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Generation behaviour
    |--------------------------------------------------------------------------
    */

    'generation' => [
        'max_active_per_user' => (int) env('LUMORA_MAX_ACTIVE_GENERATIONS', 3),
        'poll_interval_seconds' => 5,
        'max_poll_attempts' => (int) env('LUMORA_MAX_POLL_ATTEMPTS', 60),
        'stale_after_minutes' => (int) env('LUMORA_STALE_GENERATION_MINUTES', 20),
        'after_response' => (bool) env('LUMORA_GENERATIONS_AFTER_RESPONSE', false),

        'size_options' => [
            ['value' => '512x512', 'width' => 512, 'height' => 512, 'label' => 'Small square', 'ratio' => '1:1'],
            ['value' => '768x768', 'width' => 768, 'height' => 768, 'label' => 'Square', 'ratio' => '1:1'],
            ['value' => '1024x1024', 'width' => 1024, 'height' => 1024, 'label' => 'Large square', 'ratio' => '1:1'],
            ['value' => '1024x1536', 'width' => 1024, 'height' => 1536, 'label' => 'Portrait', 'ratio' => '2:3'],
            ['value' => '1536x1024', 'width' => 1536, 'height' => 1024, 'label' => 'Landscape', 'ratio' => '3:2'],
            ['value' => '1536x1536', 'width' => 1536, 'height' => 1536, 'label' => 'XL square', 'ratio' => '1:1'],
            ['value' => '1024x1792', 'width' => 1024, 'height' => 1792, 'label' => 'Tall', 'ratio' => '4:7'],
            ['value' => '1792x1024', 'width' => 1792, 'height' => 1024, 'label' => 'Wide', 'ratio' => '7:4'],
        ],

        'quality_options' => [
            ['value' => 'standard', 'label' => 'Standard', 'description' => 'Fast drafts and everyday concepts.'],
            ['value' => 'hd', 'label' => 'HD', 'description' => 'Sharper detail and richer lighting.'],
        ],

        'default_size' => '1024x1024',
        'default_quality' => 'standard',
    ],

    /*
    |--------------------------------------------------------------------------
    | Pixelforge provider
    |--------------------------------------------------------------------------
    |
    | Credentials and the base URL are read from the environment. Endpoint
    | paths, header names and signature format are configurable so the
    | integration can follow the Pixelforge spec without code changes.
    |
    */

    'provider' => [
        'name' => 'pixelforge',
        'base_url' => env('PIXELFORGE_BASE_URL', 'https://api.pixelforge.dev'),
        'api_key' => env('PIXELFORGE_API_KEY'),
        'webhook_secret' => env('PIXELFORGE_WEBHOOK_SECRET'),
        'timeout' => (int) env('PIXELFORGE_TIMEOUT', 30),
        'connect_timeout' => (int) env('PIXELFORGE_CONNECT_TIMEOUT', 10),
        'requests_per_minute' => (int) env('PIXELFORGE_REQUESTS_PER_MINUTE', 60),

        'paths' => [
            'generations' => env('PIXELFORGE_GENERATIONS_PATH', '/v1/generations'),
            'usage' => env('PIXELFORGE_USAGE_PATH', '/v1/usage'),
        ],

        'signature' => [
            'header' => env('PIXELFORGE_SIGNATURE_HEADER', 'X-Pixelforge-Signature'),
            'timestamp_header' => env('PIXELFORGE_TIMESTAMP_HEADER', 'X-Pixelforge-Timestamp'),
            'tolerance_seconds' => (int) env('PIXELFORGE_SIGNATURE_TOLERANCE', 300),
            // "timestamped" signs "{timestamp}.{rawBody}", "raw" signs the raw body.
            'scheme' => env('PIXELFORGE_SIGNATURE_SCHEME', 'timestamped'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Pricing (provider rates, in micro-dollars)
    |--------------------------------------------------------------------------
    |
    | Estimated provider cost uses the per-image rate card below. Actual
    | provider cost is derived from reported token usage where available and
    | falls back to the rate card. A 25% markup is applied to both.
    |
    */

    'pricing' => [
        'markup_basis_points' => (int) env('LUMORA_MARKUP_BASIS_POINTS', 2_500),

        'image_rates_micros' => [
            '512x512' => ['standard' => 10_000, 'hd' => 20_000],
            '768x768' => ['standard' => 15_000, 'hd' => 30_000],
            '1024x1024' => ['standard' => 20_000, 'hd' => 40_000],
            '1024x1536' => ['standard' => 28_000, 'hd' => 56_000],
            '1536x1024' => ['standard' => 28_000, 'hd' => 56_000],
            '1536x1536' => ['standard' => 35_000, 'hd' => 70_000],
            '1024x1792' => ['standard' => 30_000, 'hd' => 60_000],
            '1792x1024' => ['standard' => 30_000, 'hd' => 60_000],
        ],

        'fallback_image_micros' => 20_000,

        'usage_rates_micros_per_million' => [
            'input_tokens' => 500_000,
            'output_tokens' => 10_000_000,
            'image_tokens' => 30_000_000,
        ],
    ],

];
