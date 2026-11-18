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

        // Pixelforge accepts exactly these three canvases.
        'size_options' => [
            ['value' => '1024x1024', 'width' => 1024, 'height' => 1024, 'label' => 'Square', 'ratio' => '1:1'],
            ['value' => '1024x1536', 'width' => 1024, 'height' => 1536, 'label' => 'Portrait', 'ratio' => '2:3'],
            ['value' => '1536x1024', 'width' => 1536, 'height' => 1024, 'label' => 'Landscape', 'ratio' => '3:2'],
        ],

        // Pixelforge quality values are low, medium and high.
        'quality_options' => [
            ['value' => 'low', 'label' => 'Low', 'description' => 'Fast drafts and everyday concepts.'],
            ['value' => 'medium', 'label' => 'Medium', 'description' => 'Balanced detail for most ideas.'],
            ['value' => 'high', 'label' => 'High', 'description' => 'Sharper detail and richer lighting.'],
        ],

        'default_size' => '1024x1024',
        'default_quality' => 'medium',

        // Provider image URLs expire 24 hours after completion, so finished
        // renders are copied onto our own disk.
        'mirror_images' => (bool) env('LUMORA_MIRROR_IMAGES', true),
        'image_disk' => env('LUMORA_IMAGE_DISK', 'public'),
        'image_max_bytes' => 15 * 1024 * 1024,
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
        'base_url' => env('PIXELFORGE_BASE_URL', 'https://pixelforge.promptedit.dev'),
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
            'header' => env('PIXELFORGE_SIGNATURE_HEADER', 'Pixelforge-Signature'),
            'timestamp_header' => env('PIXELFORGE_TIMESTAMP_HEADER', 'X-Pixelforge-Timestamp'),
            'tolerance_seconds' => (int) env('PIXELFORGE_SIGNATURE_TOLERANCE', 300),
            // "timestamped" signs "{timestamp}.{rawBody}", "raw" signs the raw body.
            // Other signature-like headers and raw-body signatures are accepted as a fallback.
            'scheme' => env('PIXELFORGE_SIGNATURE_SCHEME', 'timestamped'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Pricing (provider rates, in micro-dollars)
    |--------------------------------------------------------------------------
    |
    | Pixelforge bills per token. Estimated provider cost follows the token
    | model documented by the provider, using the maximum reasoning budget so
    | the hold is always enough. Actual cost uses the rates returned with the
    | generation, then these rates, then a flat fallback. A 25% markup is
    | applied to both.
    |
    */

    'pricing' => [
        'markup_basis_points' => (int) env('LUMORA_MARKUP_BASIS_POINTS', 2_500),

        // $5.00, $10.00, $40.00 and $12.50 per million tokens.
        'usage_rates_micros_per_million' => [
            'input_text_tokens' => 5_000_000,
            'input_image_tokens' => 10_000_000,
            'output_tokens' => 40_000_000,
            'reasoning_tokens' => 12_500_000,
        ],

        'characters_per_text_token' => 4,
        'text_token_uncertainty_basis_points' => 1_500,
        'reference_image_tokens' => 765,

        // Exact output tokens by quality and size.
        'output_token_estimates' => [
            'low' => ['1024x1024' => 280, '1536x1024' => 420, '1024x1536' => 420],
            'medium' => ['1024x1024' => 1_100, '1536x1024' => 1_650, '1024x1536' => 1_650],
            'high' => ['1024x1024' => 4_300, '1536x1024' => 6_450, '1024x1536' => 6_450],
        ],

        // Reasoning is variable, so the estimate assumes the maximum.
        'reasoning_token_max' => ['low' => 500, 'medium' => 2_000, 'high' => 8_000],

        'fallback_image_micros' => 20_000,
    ],

];
