@php
    $seo = array_merge([
        'title' => config('lumora.seo.title'),
        'description' => config('lumora.seo.description'),
        'canonical' => url()->current(),
        'robots' => 'index, follow, max-image-preview:large',
        'og_type' => 'website',
        'og_image' => url('/images/og-cover.png'),
        'site_name' => config('lumora.brand.name'),
        'twitter' => config('lumora.seo.twitter'),
        'json_ld' => null,
    ], $page['props']['seo'] ?? []);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $seo['title'] }}</title>
    <meta name="description" content="{{ $seo['description'] }}">
    <meta name="robots" content="{{ $seo['robots'] }}">
    <meta name="theme-color" content="#F8FAFC">
    <link rel="canonical" href="{{ $seo['canonical'] }}">

    <meta property="og:type" content="{{ $seo['og_type'] }}">
    <meta property="og:site_name" content="{{ $seo['site_name'] }}">
    <meta property="og:title" content="{{ $seo['title'] }}">
    <meta property="og:description" content="{{ $seo['description'] }}">
    <meta property="og:url" content="{{ $seo['canonical'] }}">
    <meta property="og:image" content="{{ $seo['og_image'] }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="{{ $seo['twitter'] }}">
    <meta name="twitter:title" content="{{ $seo['title'] }}">
    <meta name="twitter:description" content="{{ $seo['description'] }}">
    <meta name="twitter:image" content="{{ $seo['og_image'] }}">

    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/favicon.svg">
    <link rel="manifest" href="/site.webmanifest">

    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link href="https://fonts.bunny.net/css?family=outfit:300,400,500,600,700|quicksand:400,500,600,700|shantell-sans:400,500,600,700&display=swap" rel="stylesheet">

    <script src="/js/theme-init.js"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @if (! empty($seo['json_ld']))
        <script type="application/ld+json">{!! json_encode($seo['json_ld'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    @endif
</head>
<body>
    @inertia

    <noscript>
        <main class="mx-auto max-w-3xl px-6 py-20">
            <h1 class="text-3xl font-semibold">Lumora — Your imagination, beautifully rendered.</h1>
            <p class="mt-4 text-base leading-relaxed">
                Lumora is an AI image generation studio with transparent pricing, live progress and a creative
                history gallery. Enable JavaScript to open the studio.
            </p>
            <p class="mt-4"><a class="underline" href="/login">Sign in</a> or <a class="underline" href="/register">create an account</a>.</p>
        </main>
    </noscript>
</body>
</html>
