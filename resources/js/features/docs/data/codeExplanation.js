/**
 * Content for the unlisted /code-explanation reference. Nothing links here.
 *
 * Every entry names a real file, what it owns, and the details that are hard to
 * rediscover by skimming. Line counts are approximate and only there to signal
 * size. Keep this file in sync when files move.
 */

export const tabs = [
    { id: 'overview', label: 'Overview', icon: 'Compass', hint: 'Start here' },
    { id: 'backend', label: 'Backend', icon: 'Server', hint: 'PHP' },
    { id: 'frontend', label: 'Frontend', icon: 'PanelsTopLeft', hint: 'Vue' },
    { id: 'data', label: 'Data & config', icon: 'Database', hint: 'Schema' },
    { id: 'flows', label: 'Flows & tests', icon: 'Workflow', hint: 'Runtime' },
];

export const sections = {
    overview: [
        {
            id: 'orientation',
            title: 'Orientation',
            summary:
                'Lumora is a Laravel 13 + Inertia v3 + Vue 3 application. There is no separate JSON API for the UI: controllers return Inertia responses, and Vue pages receive plain arrays as props.',
            entries: [
                {
                    path: 'README.md',
                    role: 'Operator handbook: local setup, Pixelforge credentials, queue/scheduler commands, deployment notes and the test commands.',
                    details: [
                        'Read this first when bringing up a fresh clone; it lists `php artisan storage:link`, the worker and `schedule:work`.',
                        'The developer guide in the app is the user-facing version of the same material.',
                    ],
                },
                {
                    path: 'project layout',
                    role: 'What each top-level directory owns.',
                    details: [
                        '`app/` — all PHP: HTTP layer, domain services, jobs, models, console commands, support helpers.',
                        '`bootstrap/app.php` — application wiring (routing, middleware, exception rendering).',
                        '`config/` — framework config plus `lumora.php`, the product configuration file.',
                        '`database/` — migrations, factories and seeders.',
                        '`resources/js/` — the Vue application, grouped by feature.',
                        '`resources/css/` — Tailwind v4 entry plus design tokens and utilities.',
                        '`routes/` — `web.php` (all HTTP routes) and `console.php` (scheduler).',
                        '`tests/` — PHPUnit unit and feature tests; feature tests boot the whole app.',
                    ],
                },
                {
                    path: 'resources/js/features/docs/pages/DeveloperGuide.vue',
                    role: 'Public, SEO-indexed developer documentation. Mirrors the real lifecycle: credentials, webhooks, pricing, mirroring, deployment.',
                    details: [
                        'Data-driven: the page is a `sections` array rendered by `DocLayout` and `DocBlocks`.',
                        'Update it when provider behaviour or setup steps change; `DocumentationTest` guards the page and sitemap.',
                    ],
                },
                {
                    path: 'resources/js/features/docs/pages/CodeExplanation.vue',
                    role: 'This page. Unlisted and public: anyone who knows the URL can read it, but nothing in the app links to it.',
                    details: [
                        'Route: `GET /code-explanation`, guarded only by the `PreventSearchIndexing` middleware.',
                        'No navigation entry, no link in the README or guides, absent from the sitemap and robots.txt.',
                        'Content lives in `resources/js/features/docs/data/codeExplanation.js`.',
                    ],
                },
            ],
        },
        {
            id: 'request-flow',
            title: 'A request, end to end',
            summary: 'Trace one signed-in page load. This is the fastest way to learn the shape of the app.',
            entries: [
                {
                    path: 'GET /generate',
                    role: 'From browser to rendered page.',
                    steps: [
                        'The browser requests `/generate`; `routes/web.php` matches it inside the `auth` middleware group.',
                        '`HandleInertiaRequests` shares `auth.user`, `flash`, `notifications`, `seo`, `brand`, `support` and `quote` with every Inertia response.',
                        '`GenerationController@create` loads the active generation, config options, pricing inputs and the user balance.',
                        '`Inertia::render(\'features/generate/pages/Generate\', [...])` returns the page name plus props.',
                        '`resources/js/app.js` resolves that name through `import.meta.glob(\'./features/**/pages/*.vue\')` and mounts the Vue page.',
                        'The page wraps itself in `AppLayout`, which renders `Sidebar`, `Topbar`, the page slot, footer and `ToastHost`.',
                        '`useSeo()` syncs `<title>`, description, canonical and robots tags with `page.props.seo` on every navigation.',
                    ],
                },
                {
                    path: 'POST /generations',
                    role: 'The write path: validate → hold credits → queue → redirect.',
                    steps: [
                        '`StoreGenerationRequest` trims the prompt, strips empty reference URLs and validates size/quality and the per-render caption choice against config.',
                        '`GenerationController@store` calls `GenerationService::create()`, which prices the request, enforces the active limit and holds credits inside a transaction.',
                        'The controller then calls `GenerationService::dispatch()`, which dispatches `SubmitGenerationJob`.',
                        'The response redirects to `/generate` with `active_uuid` in the session and a flash success message.',
                        '`Generate.vue` polls `GET /generations/{uuid}/status` as a fallback while webhooks do the primary work.',
                    ],
                },
            ],
        },
        {
            id: 'change-map',
            title: 'Where to change what',
            summary: 'The short answer to the questions that come up most often.',
            entries: [
                {
                    path: 'config/lumora.php',
                    role: 'Product configuration: markups, packages, canvases, qualities, provider endpoints, token rates and mirroring.',
                    details: ['Prefer changing config over scattering constants in services.'],
                },
                {
                    path: 'app/Services/Pixelforge/PricingCalculator.php',
                    role: 'All money math: estimates, actual provider cost, markup, charge.',
                    details: ['If a price looks wrong, start here, then compare `resources/js/lib/pricing.js`.'],
                },
                {
                    path: 'resources/js/lib/pricing.js',
                    role: 'Client mirror of the estimator for the live price preview.',
                    details: ['Server values are authoritative; this file must stay numerically identical.'],
                },
                {
                    path: 'resources/css/tokens.css',
                    role: 'Brand palette, typography tokens, light/dark surface colours, shadows.',
                    details: ['Change colours here; components consume `bg-surface`, `text-ink`, `border-line`, `brand-*`.'],
                },
                {
                    path: 'app/Http/Controllers/GenerationController.php',
                    role: 'Studio props, gallery filters/pagination, status endpoint, delete behaviour.',
                },
                {
                    path: 'app/Jobs/SubmitGenerationJob.php',
                    role: 'Provider request shape, idempotency key, retry/refund behaviour. Change this when the provider API changes.',
                },
                {
                    path: 'resources/js/layouts/AppLayout.vue',
                    role: 'Signed-in navigation items, page container width, app footer.',
                    details: ['Admin link is appended only when `auth.user.is_admin` is true.'],
                },
                {
                    path: 'app/Services/Generations/GenerationFinalizer.php',
                    role: 'The single place a generation becomes terminal and the wallet settles or refunds.',
                    details: ['Webhooks, polling and expiry all funnel here; avoid adding settlement logic anywhere else.'],
                },
                {
                    path: 'app/Support/Seo.php',
                    role: 'Per-path titles, descriptions, robots directives and JSON-LD.',
                    details: ['Add a `match` arm for each public page; unlisted pages return `noindex, nofollow`.'],
                },
                {
                    path: 'resources/js/features/docs/data/codeExplanation.js',
                    role: 'This reference. Add an entry when you add a file so the map stays complete.',
                },
            ],
        },
        {
            id: 'money-model',
            title: 'Money model in one screen',
            summary: 'Integer micro-dollars everywhere. No floats are ever stored or summed.',
            entries: [
                {
                    path: 'app/Support/Money.php',
                    role: 'The only money conversion helper. 1 USD = 1,000,000 micros; 1 credit = 10,000 micros ($0.01).',
                    details: [
                        '`usdToMicros()` and `microsToUsd()` use `Brick\\Math\\BigDecimal` with `RoundingMode::HalfUp`.',
                        '`withMarkup()` and `applyMarkup()` round the markup **up**, so the house never under-charges.',
                        'Formatting helpers exist only for display strings.',
                    ],
                },
                {
                    path: 'wallet invariant',
                    role: '`users.credit_balance_micros` must always equal the starting balance plus the sum of ledger rows.',
                    details: [
                        '`CreditService` writes the balance and the ledger row together inside a transaction, with the user row locked.',
                        '`unique(generation_id, type)` plus `record()` idempotency make hold/settlement/refund exactly-once.',
                        'Settlement never lets the balance go negative; any shortfall is recorded in the ledger meta and generation meta.',
                    ],
                },
            ],
        },
    ],

    backend: [
        {
            id: 'controllers',
            title: 'Controllers (HTTP)',
            summary: 'Thin: validate input, call a service, return an Inertia page or a redirect. Business rules live in `app/Services`.',
            entries: [
                {
                    path: 'app/Http/Controllers/Controller.php',
                    lineCount: 6,
                    role: 'Empty base class. Every controller extends it.',
                },
                {
                    path: 'app/Http/Controllers/LandingController.php',
                    lineCount: 47,
                    role: 'Public home page. Loads the latest six succeeded renders as the showcase and maps credit packages for pricing.',
                    details: ['Passes `isAuthenticated` and `canRegister` so the nav/CTAs adapt without client auth checks.'],
                },
                {
                    path: 'app/Http/Controllers/DashboardController.php',
                    lineCount: 37,
                    role: 'Signed-in overview: balance, totals, succeeded, processing, spend and failed counts, six recent creations, current active render.',
                    details: ['`providerReady` tells the UI whether `PIXELFORGE_API_KEY` is configured.'],
                },
                {
                    path: 'app/Http/Controllers/GenerationController.php',
                    lineCount: 112,
                    role: 'The studio and gallery: create page, store, index with filters/pagination, status JSON, soft delete.',
                    details: [
                        '`create()` exposes sizes, qualities, defaults, pricing inputs, balance, active count and the in-flight generation.',
                        '`index()` filters by `status` and `q`, paginates 12, and returns per-status counts for the tabs.',
                        '`status()` authorises with `Gate::authorize(\'view\', ...)` and returns the fresh balance and active count.',
                        '`destroy()` only allows terminal rows, then soft deletes.',
                    ],
                },
                {
                    path: 'app/Http/Controllers/BillingController.php',
                    lineCount: 60,
                    role: 'Wallet page and the intentionally fake demo purchase.',
                    details: [
                        '`index()` maps packages with formatted USD/credits and paginates the ledger.',
                        '`purchase()` locks the user, calls `CreditService::purchase()` with `purchase_{token}` as an idempotent reference. Replace before taking real money.',
                    ],
                },
                {
                    path: 'app/Http/Controllers/SettingsController.php',
                    lineCount: 58,
                    role: 'Profile, password and account deletion.',
                    details: [
                        'Email changes mark the address verified immediately (no verification flow is enabled).',
                        'Password changes rotate `remember_token` so other sessions are invalidated.',
                        'Account deletion checks the current password, logs out, invalidates the session and deletes the user.',
                    ],
                },
                {
                    path: 'app/Http/Controllers/Admin/AdminDashboardController.php',
                    lineCount: 33,
                    role: 'Admin metrics page and the manual usage reconcile action.',
                    details: [
                        '`index()` passes `AdminMetrics::overview()` plus `providerReady`.',
                        '`reconcile()` calls `UsageReconciler::sync(now()->subDays(2), now())` and flashes the result or the provider error.',
                    ],
                },
                {
                    path: 'app/Http/Controllers/DocumentationController.php',
                    lineCount: 15,
                    role: 'Renders the public User Guide and Developer Guide Inertia pages.',
                },
                {
                    path: 'app/Http/Controllers/CodeExplanationController.php',
                    lineCount: 16,
                    role: 'Renders this unlisted reference. There is no access gate; discoverability comes only from the URL.',
                },
                {
                    path: 'app/Http/Controllers/SitemapController.php',
                    lineCount: 41,
                    role: 'Builds `sitemap.xml` from named routes: home, guide, developer guide, terms, privacy.',
                    details: ['Authenticated and unlisted routes are deliberately absent.'],
                },
                {
                    path: 'app/Http/Controllers/Webhooks/PixelforgeWebhookController.php',
                    lineCount: 90,
                    role: 'Verifies the HMAC signature, parses the body, hands the event to `WebhookProcessor`, and always answers JSON.',
                    details: [
                        'Looks for the configured signature header, then any header containing `sig`; supports a separate timestamp header.',
                        'Valid → `200`; bad signature → `401`; malformed JSON → `400`; processing exception → `500`.',
                        'CSRF is disabled for `webhooks/*` in `bootstrap/app.php`.',
                    ],
                },
            ],
        },
        {
            id: 'auth',
            title: 'Authentication',
            summary: 'Hand-written session auth. No Breeze/Jetstream; password reset uses Laravel\'s built-in broker.',
            entries: [
                {
                    path: 'app/Http/Controllers/Auth/AuthenticatedSessionController.php',
                    lineCount: 54,
                    role: 'Login form, credential check with a 5-attempt rate limit per email+IP, session regeneration, `last_login_at`.',
                    details: ['Failed attempts hit the limiter for 60 seconds; success clears it.', 'Redirects to `intended(dashboard)`.'],
                },
                {
                    path: 'app/Http/Controllers/Auth/RegisteredUserController.php',
                    lineCount: 55,
                    role: 'Registration with terms acceptance and the welcome credit grant.',
                    details: [
                        'Creates the user and grants `welcome_bonus_micros` in one transaction, reference `welcome:{user id}` so it cannot double-grant.',
                        'Logs the user in and redirects to the dashboard.',
                    ],
                },
                {
                    path: 'app/Http/Controllers/Auth/PasswordResetLinkController.php',
                    lineCount: 30,
                    role: 'Sends the reset link and always reports success to avoid account enumeration.',
                },
                {
                    path: 'app/Http/Controllers/Auth/NewPasswordController.php',
                    lineCount: 47,
                    role: 'Validates the reset token, updates the password and flashes a status message.',
                },
            ],
        },
        {
            id: 'validation',
            title: 'Form requests & API resources',
            summary: 'Validation is declarative; resources shape JSON for the status endpoint and paginated lists.',
            entries: [
                {
                    path: 'app/Http/Requests/StoreGenerationRequest.php',
                    lineCount: 66,
                    role: 'Validates prompt (10–2000 chars), size/quality against config, up to 4 reference URLs and the per-render caption preference; custom messages and attribute names.',
                    details: ['`prepareForValidation()` trims the prompt, drops blank reference strings and casts `remove_captions` to a boolean.'],
                },
                {
                    path: 'app/Http/Requests/PurchaseCreditsRequest.php',
                    lineCount: 18,
                    role: 'Requires a known package key and an alphanumeric `purchase_token` used as the idempotency reference.',
                },
                {
                    path: 'app/Http/Requests/UpdateProfileRequest.php',
                    lineCount: 25,
                    role: 'Name and unique lowercase email for the current user.',
                },
                {
                    path: 'app/Http/Requests/UpdatePasswordRequest.php',
                    lineCount: 18,
                    role: 'Current password check plus confirmed new password (min 8).',
                },
                {
                    path: 'app/Http/Resources/GenerationResource.php',
                    lineCount: 55,
                    role: 'Serialises a generation for the gallery, detail modal and polling: dimensions, costs as USD and credits, usage, meta error fields.',
                    details: ['Exposes both estimated and actual amounts so the UI can show the settled price.'],
                },
                {
                    path: 'app/Http/Resources/CreditTransactionResource.php',
                    lineCount: 25,
                    role: 'Serialises ledger rows with signed amount, credit equivalent, running balance, package and optional generation UUID.',
                },
            ],
        },
        {
            id: 'middleware',
            title: 'Middleware',
            summary: 'Three custom middleware: admin gate, Inertia shared props, and the noindex guard.',
            entries: [
                {
                    path: 'app/Http/Middleware/HandleInertiaRequests.php',
                    lineCount: 61,
                    role: 'Shares auth user, flash (success/error/info), notifications, SEO, brand and support props with every page.',
                    details: [
                        'Notifications are the last six settled generations from the past day.',
                        '`seo` is resolved per path by `App\\Support\\Seo`, which is why crawlers get real tags in the Blade document.',
                    ],
                },
                {
                    path: 'app/Http/Middleware/EnsureUserIsAdmin.php',
                    lineCount: 13,
                    role: 'Aborts 403 unless the user is an admin; aliased as `admin` and applied to the `/admin` group.',
                },
                {
                    path: 'app/Http/Middleware/PreventSearchIndexing.php',
                    lineCount: 22,
                    role: 'Sets `X-Robots-Tag: noindex, nofollow` on unlisted pages.',
                    details: ['Applied to `/code-explanation` only. It does not restrict access.'],
                },
            ],
        },
        {
            id: 'generation-services',
            title: 'Generation domain',
            summary: 'Create → submit → settle/refund. The finalizer is the only place that writes terminal state and moves money.',
            entries: [
                {
                    path: 'app/Services/Generations/GenerationService.php',
                    lineCount: 149,
                    role: 'Creates the row: price estimate, active-render limit, dimensions, credit hold, caption preference, optional dispatch, status payload, stale expiry.',
                    details: [
                        '`create()` does **not** queue anything; callers must call `dispatch()`. This is deliberate so tests and scripts control timing.',
                        'Stores the studio\'s `remove_captions` choice in `meta`, which `MirrorGenerationImage` reads per render.',
                        '`dispatch()` honours `lumora.generation.after_response` for `dispatch()->afterResponse()`.',
                        '`expireStale()` fails rows untouched beyond `stale_after_minutes` through the finalizer, which refunds.',
                    ],
                },
                {
                    path: 'app/Services/Generations/GenerationFinalizer.php',
                    lineCount: 226,
                    role: 'Idempotent success/failure/processing transitions inside `lockForUpdate` transactions.',
                    details: [
                        'Guards: already settled → no-op; event older than `last_provider_event_at` → ignored.',
                        'Success: prices actual usage, charges via `CreditService::settleGeneration()`, stores image URLs/usage/meta and queues mirroring after commit.',
                        'Failure: refunds the hold, records `failure_source` and `meta.provider_error`, and keeps the customer charge at zero.',
                        '`imageUrl()`, `providerGenerationId()` and `providerStatus()` accept many payload shapes (webhook `data.object` and polled objects).',
                    ],
                },
                {
                    path: 'app/Services/Generations/WebhookProcessor.php',
                    lineCount: 228,
                    role: 'Deduplicates by event id, finds the generation, normalises status, calls the finalizer, and records every outcome.',
                    details: [
                        'Duplicate event → `duplicate`; ping → `ignored`; unknown generation → `unmatched` (stored for replay).',
                        'Out-of-order events are ignored and recorded with `error=out_of_order`.',
                        '`replayPending()` retries stored unmatched/failed events; called by `lumora:reconcile-usage`.',
                        'Event id falls back to `evt_` + SHA-256 of the raw body, so payloads without ids still dedupe.',
                    ],
                },
                {
                    path: 'app/Services/Generations/ImageCaptionStripper.php',
                    lineCount: 301,
                    role: 'Optional cleanup that paints the provider\'s drawn-on caption out of mirrored PNGs, leaving only the artwork and the PIXELFORGE watermark.',
                    details: [
                        'Reads the per-render caption preference before applying the stripper, so each render follows the studio checkbox.',
                        'Finds caption rows by counting near-white, neutral pixels, so artwork elsewhere is never touched.',
                        'Grows each stroke by 2px to swallow antialiasing, then blends the background on both sides across the run.',
                        'Protects the top 78 rows (the watermark) and skips bright runs wider than 120px (artwork highlights).',
                        'Returns the original bytes when the image has no captions or GD cannot read it.',
                    ],
                },
            ],
        },
        {
            id: 'pixelforge',
            title: 'Pixelforge integration',
            summary: 'The provider is isolated behind a client, an exception type, a pricer, a signature verifier and a usage reconciler.',
            entries: [
                {
                    path: 'app/Services/Pixelforge/PixelforgeClient.php',
                    lineCount: 139,
                    role: 'HTTP client: create generation (with `Idempotency-Key`), fetch generation, list usage.',
                    details: [
                        'Base URL is the origin only; paths come from config (`/v1/generations`, `/v1/usage`).',
                        'Sends bearer token, JSON, `X-Lumora-Client`, connect/read timeouts and honours `Retry-After`.',
                        'Logs method, URL, status, duration and request id; failures also log the first 500 chars of the body.',
                    ],
                },
                {
                    path: 'app/Services/Pixelforge/PixelforgeException.php',
                    lineCount: 37,
                    role: 'Typed provider failure with status, error code, retry delay and three predicates.',
                    details: [
                        '`isClientError()` = 4xx; `isRetryable()` = 429/5xx; `isNotFound()` lets submit/poll treat 404 as transient.',
                        '`retryDelay()` respects `Retry-After`, otherwise the caller\'s default.',
                    ],
                },
                {
                    path: 'app/Services/Pixelforge/PricingCalculator.php',
                    lineCount: 190,
                    role: 'The money engine: estimate from prompt size/quality, derive actual cost from payload, apply markup.',
                    details: [
                        'Estimate: text tokens (chars / 4, +15% uncertainty) + reference image tokens + output tokens by quality/size + **maximum** reasoning tokens.',
                        'Actual: explicit reported cost → token usage priced with payload `rates` → configured rates → documented estimate.',
                        '`chargeForProviderCost()` rounds the markup up in basis points (default 2500 = 25%).',
                        '`usageFrom()` reads `usage`, `data.object.usage` or `data.usage`.',
                    ],
                },
                {
                    path: 'app/Services/Pixelforge/PriceEstimate.php',
                    lineCount: 30,
                    role: 'Immutable value object for an estimate: provider, markup, total micros plus USD strings.',
                },
                {
                    path: 'app/Services/Pixelforge/WebhookSignature.php',
                    lineCount: 110,
                    role: 'Stateless HMAC verification for `timestamped` (`t=…,v1=…`) and `raw` schemes.',
                    details: [
                        'Timestamped signatures sign `{timestamp}.{rawBody}` and reject skew beyond the tolerance (300s).',
                        'Uses `hash_equals` for constant-time comparison; accepts multiple candidate signatures.',
                        'Header discovery fallback is in the webhook controller, not here.',
                    ],
                },
                {
                    path: 'app/Services/Pixelforge/UsageReconciler.php',
                    lineCount: 128,
                    role: 'Pulls `GET /v1/usage` for a date window, upserts local usage rows and links them to generations.',
                    details: [
                        'Handles several response envelopes (`data`, `usage`, `items`, a bare list) and idempotent upserts by provider usage id.',
                        'Returns fetched/stored/provider micros/missing-generation counts for the command and admin UI.',
                    ],
                },
            ],
        },
        {
            id: 'billing-service',
            title: 'Billing',
            summary: 'One service owns every wallet mutation; the ledger is the audit trail.',
            entries: [
                {
                    path: 'app/Services/Billing/CreditService.php',
                    lineCount: 221,
                    role: 'Lock, hold, settle, refund, purchase, grant, summary and the private ledger writer.',
                    details: [
                        '`record()` is idempotent per `(generation, type)`, rejects negative balances, and writes balance + ledger together.',
                        'Settlement replaces the hold with the actual charge: negative difference refunds, positive difference debits (capped at balance, remainder recorded as `shortfall_micros`).',
                        '`purchase()` and `grant()` are idempotent by unique `reference`.',
                        '`summary()` reports balance, purchased, bonus, consumed (succeeded actual charges) and pending holds.',
                    ],
                },
            ],
        },
        {
            id: 'admin-service',
            title: 'Admin metrics',
            summary: 'Aggregates provider cost, customer charges, profit, daily series, top spenders and usage variance.',
            entries: [
                {
                    path: 'app/Services/Admin/AdminMetrics.php',
                    lineCount: 113,
                    role: 'Builds the whole admin dashboard payload.',
                    details: [
                        'Uses `withTrashed()` so soft-deleted generations still appear in historical totals.',
                        'Daily series fills empty days with zeros across the selected range (7/14/30/90, default 14).',
                        'Per-user breakdown is a left join over settled generations, top 10 by charge.',
                        'Reconciliation compares summed `provider_usage_records` with generation provider costs and reports variance + last sync.',
                    ],
                },
            ],
        },
        {
            id: 'jobs',
            title: 'Queued jobs',
            summary: 'Database queue. Submission is unique per generation; polling reschedules itself; mirroring copies the render locally.',
            entries: [
                {
                    path: 'app/Jobs/SubmitGenerationJob.php',
                    lineCount: 120,
                    role: 'Submits to Pixelforge, records the provider id, branches on immediate status, queues the first poll.',
                    details: [
                        '`ShouldBeUnique` (`generation:{id}`, 600s) prevents double submission; 5 tries with 5/15/30/60s backoff and a per-minute rate limiter.',
                        'Sends only the documented fields: `prompt`, `size`, `quality`, optional `reference_images`, string `metadata`.',
                        '4xx (except 404) fails and refunds immediately; 404/429/5xx retry with the same idempotency key.',
                        '`failed()` refunds and marks `provider_unavailable` when retries are exhausted.',
                    ],
                },
                {
                    path: 'app/Jobs/PollGenerationJob.php',
                    lineCount: 84,
                    role: 'Webhook fallback: fetches the provider generation, applies the status, reschedules while processing.',
                    details: [
                        'Fails fast without a provider id or after `max_poll_attempts`.',
                        '404/5xx/429 release the job for another attempt; other 4xx fail and refund.',
                        'Releases every `poll_interval_seconds` (default 5s) while the status is non-terminal.',
                    ],
                },
                {
                    path: 'app/Jobs/MirrorGenerationImage.php',
                    lineCount: 159,
                    role: 'Downloads the finished render to the public disk because provider URLs expire after 24 hours.',
                    details: [
                        'Skips when mirroring is disabled, the row is not succeeded, or the URL is already local.',
                        'Validates http(s), enforces `image_max_bytes`, picks an extension from content type, stores `generations/{uuid}.{ext}`.',
                        'Reads the per-render `meta.remove_captions` flag (falling back to `strip_captions`) and runs `ImageCaptionStripper` on PNGs, then records `captions_stripped_at`.',
                        'Moves the original URL to `meta.provider_image_url` and records `image_mirrored_at`.',
                        '3 tries with 10/60/300s backoff; failure logs a warning (the provider URL is still usable briefly).',
                    ],
                },
            ],
        },
        {
            id: 'commands',
            title: 'Console commands',
            summary: 'Polling, expiry and reconciliation run on the scheduler; caption cleanup is on demand.',
            entries: [
                {
                    path: 'app/Console/Commands/PollGenerationsCommand.php',
                    lineCount: 27,
                    role: '`lumora:poll-generations` — queues polls for in-flight rows older than 8 seconds, max 100 per run.',
                },
                {
                    path: 'app/Console/Commands/ExpireGenerationsCommand.php',
                    lineCount: 15,
                    role: '`lumora:expire-generations` — fails and refunds rows stuck longer than the stale timeout.',
                },
                {
                    path: 'app/Console/Commands/ReconcileUsageCommand.php',
                    lineCount: 30,
                    role: '`lumora:reconcile-usage {--days=2}` — replays pending webhooks, syncs provider usage, reports missing generations.',
                },
                {
                    path: 'app/Console/Commands/StripImageCaptionsCommand.php',
                    lineCount: 80,
                    role: '`lumora:strip-image-captions` — cleans opted-in renders and legacy rows covered by the configured default, including soft-deleted rows, while respecting an explicit per-render keep.',
                },
            ],
        },
        {
            id: 'models',
            title: 'Models',
            summary: 'Five Eloquent models. Soft-deleted generations stay in admin history; ledger and event tables are append-only.',
            entries: [
                {
                    path: 'app/Models/User.php',
                    lineCount: 72,
                    role: 'Auth user with balance, admin flag, initials, `toInertiaArray()`; has many generations and ledger rows.',
                    details: ['`password` is hashed by the cast; `password` and `remember_token` are hidden from arrays.'],
                },
                {
                    path: 'app/Models/Generation.php',
                    lineCount: 127,
                    role: 'The central record: prompt, canvas, status, provider ids, estimates, actuals, usage, meta, timestamps, soft deletes.',
                    details: [
                        'Statuses: `pending`, `processing`, `succeeded`, `failed`; `TERMINAL_STATUSES` drives most guards.',
                        'Scopes: `ownedBy()`, `processing()`, `settled()`; helpers `isTerminal()`, `isSettled()`, `markupMicros()`.',
                        'Route key is `uuid`, not the numeric id; UUID is generated on create.',
                    ],
                },
                {
                    path: 'app/Models/CreditTransaction.php',
                    lineCount: 55,
                    role: 'Append-only wallet ledger. Types: purchase, bonus, hold, settlement, refund, adjustment.',
                    details: ['Unique `(generation_id, type)` enforces one hold/settlement/refund per generation.'],
                },
                {
                    path: 'app/Models/WebhookEvent.php',
                    lineCount: 40,
                    role: 'Every delivered event: provider event id, type, status, links, signature flag, processed/ignored, payload, headers, error.',
                    details: ['`payloadArray()` decodes the stored raw body for replay.'],
                },
                {
                    path: 'app/Models/ProviderUsageRecord.php',
                    lineCount: 35,
                    role: 'Reconciled usage rows keyed by provider usage id, with tokens, cost and optional generation link.',
                },
            ],
        },
        {
            id: 'platform',
            title: 'Policies, providers, support',
            summary: 'Authorisation, service registration and the two shared helpers.',
            entries: [
                {
                    path: 'app/Policies/GenerationPolicy.php',
                    lineCount: 15,
                    role: '`view`: owner or admin. `delete`: owner only.',
                    details: ['Auto-discovered by Laravel. Enforced with `Gate::authorize()` in the controller.'],
                },
                {
                    path: 'app/Providers/AppServiceProvider.php',
                    lineCount: 20,
                    role: 'Empty on purpose. Laravel auto-discovers policies; services are resolved from the container without bindings.',
                },
                {
                    path: 'app/Support/Money.php',
                    lineCount: 80,
                    role: 'Micro-dollar conversion, USD/credit formatting, markup helpers.',
                    details: ['Use this instead of `round()` or float math anywhere money is involved.'],
                },
                {
                    path: 'app/Support/Seo.php',
                    lineCount: 100,
                    role: 'Path → title/description/indexable map, canonical URL, OG/Twitter defaults and JSON-LD.',
                    details: ['Unlisted pages fall through to `noindex, nofollow` and no JSON-LD.'],
                },
            ],
        },
    ],

    frontend: [
        {
            id: 'shell',
            title: 'App shell',
            summary: 'Signed-in chrome: collapsible desktop sidebar, mobile drawer, topbar, footer and flash toasts.',
            entries: [
                {
                    path: 'resources/js/layouts/AppLayout.vue',
                    lineCount: 97,
                    role: 'Composes the authenticated layout and owns the nav list, flash→toast watcher and mobile drawer state.',
                    details: [
                        'Nav: Overview, Generate Image, My Creations, Credits & Billing, Settings; Admin Dashboard is appended for admins.',
                        'Container is `max-w-7xl` by default and can be overridden with the `container` prop.',
                        'Mobile gutters are `px-5`; desktop goes to `lg:px-8`.',
                    ],
                },
                {
                    path: 'resources/js/layouts/AuthLayout.vue',
                    lineCount: 75,
                    role: 'Split auth layout: brand/visual panel plus the form card, theme toggle and back-to-home link.',
                },
                {
                    path: 'resources/js/components/layout/Sidebar.vue',
                    lineCount: 139,
                    role: 'Fixed desktop navigation with active-route highlighting, collapse toggle and credit balance chip.',
                },
                {
                    path: 'resources/js/components/layout/Topbar.vue',
                    lineCount: 174,
                    role: 'Sticky header: mobile menu button, logo, live creations search (debounced), theme toggle, notifications and the user menu.',
                },
                {
                    path: 'resources/js/components/layout/MobileDrawer.vue',
                    lineCount: 119,
                    role: 'Slide-in navigation under `lg`, with backdrop, focus handling, balance and sign-out.',
                },
                {
                    path: 'resources/js/components/layout/NotificationsDropdown.vue',
                    lineCount: 80,
                    role: 'Recent settled renders from shared props; each item links to the creation and labels success/failure with a relative time.',
                },
                {
                    path: 'resources/js/components/layout/UserMenu.vue',
                    lineCount: 89,
                    role: 'Avatar dropdown: identity, balance, settings, billing, admin (if allowed) and logout.',
                },
                {
                    path: 'resources/js/components/layout/PageHeader.vue',
                    lineCount: 29,
                    role: 'Reusable page heading: eyebrow, title, description and an actions slot.',
                },
            ],
        },
        {
            id: 'ui',
            title: 'UI kit',
            summary: 'Small, unopinionated primitives. Prefer composing these over writing one-off markup.',
            entries: [
                { path: 'resources/js/components/ui/Button.vue', lineCount: 55, role: 'Variants (primary/secondary/ghost/danger), sizes, loading spinner, icon slots, disabled handling.' },
                { path: 'resources/js/components/ui/Card.vue', lineCount: 32, role: 'Surface card with padding, tone (`surface`/`elevated`/`glass`/`quiet`), optional hover lift and polymorphic `as`.' },
                { path: 'resources/js/components/ui/Input.vue', lineCount: 30, role: 'Text input with label/error/hint support via `FormField` styling.' },
                { path: 'resources/js/components/ui/Textarea.vue', lineCount: 21, role: 'Auto-sized prompt textarea with the same field styling.' },
                { path: 'resources/js/components/ui/FormField.vue', lineCount: 35, role: 'Label + hint + error wrapper that wires `aria-describedby` and `aria-invalid`.' },
                { path: 'resources/js/components/ui/Badge.vue', lineCount: 47, role: 'Tone-aware pill for statuses, counts and tags.' },
                { path: 'resources/js/components/ui/Modal.vue', lineCount: 79, role: 'Teleported dialog with backdrop, Escape handling, body scroll lock and focus restore.' },
                { path: 'resources/js/components/ui/Dropdown.vue', lineCount: 50, role: 'Trigger + panel with outside-click close and slot content.' },
                { path: 'resources/js/components/ui/Pagination.vue', lineCount: 77, role: 'Inertia links for paginated props; preserves query strings.' },
                { path: 'resources/js/components/ui/EmptyState.vue', lineCount: 21, role: 'Icon, title, description and action slot for empty lists.' },
                { path: 'resources/js/components/ui/Skeleton.vue', lineCount: 8, role: 'Shimmer placeholder block for deferred/loading content.' },
                { path: 'resources/js/components/ui/Spinner.vue', lineCount: 11, role: 'Inline SVG spinner used by buttons and async panels.' },
                { path: 'resources/js/components/ui/StatCard.vue', lineCount: 36, role: 'Metric tile with label, value, icon and delta/trend text.' },
                { path: 'resources/js/components/ui/ToastHost.vue', lineCount: 51, role: 'Fixed toast stack fed by `useToast()`; auto-dismiss and aria-live.' },
            ],
        },
        {
            id: 'brand',
            title: 'Brand components',
            summary: 'Domain-specific pieces shared by several pages.',
            entries: [
                { path: 'resources/js/components/brand/Logo.vue', lineCount: 46, role: 'Orb mark + wordmark, sizes, optional tagline, configurable link target.' },
                { path: 'resources/js/components/brand/ThemeToggle.vue', lineCount: 23, role: 'Sun/moon button that calls `useTheme().toggleTheme()`.' },
                { path: 'resources/js/components/brand/Avatar.vue', lineCount: 34, role: 'Initials avatar on the brand gradient, with size variants and automatic initials.' },
                { path: 'resources/js/components/brand/StatusBadge.vue', lineCount: 14, role: 'Maps `pending`/`processing`/`succeeded`/`failed` to label, icon and tone.' },
                { path: 'resources/js/components/brand/GenerationCard.vue', lineCount: 70, role: 'Gallery tile: contained image (never cropped), full prompt, status and cost below the image so nothing covers the render.' },
                { path: 'resources/js/components/brand/GenerationDetailModal.vue', lineCount: 230, role: 'Full detail: contained image, prompt copy, dimensions, timeline, estimate vs actual cost breakdown, real file download, delete and provider/failure errors.' },
                { path: 'resources/js/components/brand/CostBreakdown.vue', lineCount: 37, role: 'Rows for provider cost, markup and customer total; used in studio and detail modal.' },
                { path: 'resources/js/components/brand/SmartImage.vue', lineCount: 60, role: 'Lazy image with three states: loaded, animated rendering placeholder, and no-preview fallback; `fit="contain"` keeps the whole render visible.' },
            ],
        },
        {
            id: 'charts',
            title: 'Charts',
            summary: 'No chart library; the admin graph is hand-built SVG so it stays tiny and themeable.',
            entries: [
                {
                    path: 'resources/js/components/charts/TrendChart.vue',
                    lineCount: 125,
                    role: 'Responsive area/line chart for the daily generation/cost series, with grid lines, labels and hover points.',
                },
            ],
        },
        {
            id: 'pages',
            title: 'Feature pages',
            summary: 'Each feature owns a folder with `pages/`. `app.js` resolves any `features/**/pages/*.vue` by name.',
            entries: [
                { path: 'resources/js/features/landing/pages/Welcome.vue', lineCount: 503, role: 'Public marketing page: hero, features, live showcase, how-it-works, pricing, FAQ, final CTA.' },
                { path: 'resources/js/features/auth/pages/Login.vue', lineCount: 108, role: 'Sign-in form with password visibility toggle, remember-me, validation errors and the forgot-password link.' },
                { path: 'resources/js/features/auth/pages/Register.vue', lineCount: 160, role: 'Registration with terms checkbox, password confirmation and the welcome-credits pitch.' },
                { path: 'resources/js/features/auth/pages/ForgotPassword.vue', lineCount: 64, role: 'Requests a reset link and shows the neutral status message.' },
                { path: 'resources/js/features/auth/pages/ResetPassword.vue', lineCount: 106, role: 'Token + email + new password form.' },
                { path: 'resources/js/features/dashboard/pages/Dashboard.vue', lineCount: 315, role: 'Overview: stat cards, active render progress, recent creations grid, provider/credit warnings.' },
                {
                    path: 'resources/js/features/generate/pages/Generate.vue',
                    lineCount: 569,
                    role: 'The studio: prompt, canvas, quality, reference URLs, per-render caption removal, live estimate, credit checks, submit, progress and result.',
                    details: [
                        'Live estimate uses `estimatePrice()` from `lib/pricing.js`; the server recalculates on submit.',
                        'The Remove provider caption checkbox posts `remove_captions`, which is stored on the generation and applied when the image is mirrored.',
                        'Polls via `useGenerationPolling()` until the generation is terminal, then shows the image and settled cost.',
                        'Disables submit when unaffordable, over the active limit, provider missing, or a request is in flight.',
                    ],
                },
                { path: 'resources/js/features/creations/pages/Creations.vue', lineCount: 311, role: 'Gallery with status tabs + counts, debounced search, pagination, cards and the detail modal. Auto-refresh pauses while the search field is focused, and the field mirrors topbar searches.' },
                { path: 'resources/js/features/billing/pages/Billing.vue', lineCount: 296, role: 'Balance/purchased/bonus/consumed summary, credit packages, demo checkout, ledger table with per-type icons.' },
                { path: 'resources/js/features/settings/pages/Settings.vue', lineCount: 308, role: 'Profile, password, account stats and danger-zone deletion, each as its own form.' },
                { path: 'resources/js/features/admin/pages/AdminDashboard.vue', lineCount: 427, role: 'KPI cards, range selector, trend chart, provider reconciliation with manual sync, per-user spend table, recent generations.' },
                { path: 'resources/js/features/docs/pages/UserGuide.vue', lineCount: 343, role: 'Public user guide (getting started, credits, studio, gallery, account, FAQ).' },
                { path: 'resources/js/features/docs/pages/DeveloperGuide.vue', lineCount: 594, role: 'Public developer guide: architecture, setup, provider API, webhooks, pricing, jobs, deployment.' },
                { path: 'resources/js/features/legal/pages/Terms.vue', lineCount: 49, role: 'Terms page; content rendered through `LegalPage`.' },
                { path: 'resources/js/features/legal/pages/Privacy.vue', lineCount: 48, role: 'Privacy page; content rendered through `LegalPage`.' },
            ],
        },
        {
            id: 'docs-ui',
            title: 'Docs & legal components',
            summary: 'Shared renderers for the long-form pages, so documentation is data instead of markup.',
            entries: [
                {
                    path: 'resources/js/features/docs/components/DocLayout.vue',
                    lineCount: 241,
                    role: 'Public guide shell: sticky table of contents, reading progress, theme toggle, guide switcher and footer.',
                },
                {
                    path: 'resources/js/features/docs/components/DocBlocks.vue',
                    lineCount: 148,
                    role: 'Renders block types: subheading, paragraph, list, steps, callout, code, table, definitions.',
                },
                {
                    path: 'resources/js/features/docs/components/RichText.vue',
                    lineCount: 44,
                    role: 'XSS-safe inline formatting for `code` and **bold**; used everywhere in docs.',
                },
                {
                    path: 'resources/js/features/docs/pages/CodeExplanation.vue',
                    lineCount: 0,
                    role: 'This unlisted reference: tabbed, collapsible, searchable and self-contained (no app navigation).',
                    details: ['Reads `codeExplanation.js`; no server props beyond the shared Inertia ones.'],
                },
                {
                    path: 'resources/js/features/legal/components/LegalPage.vue',
                    lineCount: 66,
                    role: 'Shared legal layout: title, last-updated date, prose sections and the public header/footer.',
                },
            ],
        },
        {
            id: 'composables',
            title: 'Composables',
            summary: 'Reusable client state and browser behaviour.',
            entries: [
                { path: 'resources/js/composables/useTheme.js', lineCount: 60, role: 'Light/dark/system preference in `localStorage`, applies the `.dark` class, updates `theme-color`, exposes `setTheme`/`toggleTheme`.' },
                { path: 'resources/js/composables/useSidebar.js', lineCount: 35, role: 'Persisted sidebar collapsed state shared across components.' },
                { path: 'resources/js/composables/useToast.js', lineCount: 41, role: 'Global toast queue with success/error/info/warning and timed dismissal.' },
                { path: 'resources/js/composables/useGenerationPolling.js', lineCount: 55, role: 'Self-rescheduling status poller that stops on terminal state, 404 or unmount.' },
                { path: 'resources/js/composables/useSeo.js', lineCount: 42, role: 'Writes title/description/canonical/OG/Twitter/robots tags from `page.props.seo` on navigation.' },
                { path: 'resources/js/composables/useClipboard.js', lineCount: 15, role: 'Copy-to-clipboard with a transient `copied` flag.' },
                { path: 'resources/js/composables/useClickOutside.js', lineCount: 11, role: 'Pointer listener that closes dropdowns when clicking outside an element ref.' },
            ],
        },
        {
            id: 'lib',
            title: 'Client libraries',
            summary: 'Tiny helpers; no Axios (removed in Inertia v3).',
            entries: [
                {
                    path: 'resources/js/lib/routes.js',
                    lineCount: 33,
                    role: 'Single list of URL strings plus `generationStatusUrl()` / `generationUrl()` helpers. Keep in sync with `routes/web.php`.',
                },
                {
                    path: 'resources/js/lib/format.js',
                    lineCount: 103,
                    role: 'Integer-safe display formatters: USD micros, credits, compact numbers, dates, relative time, percentages.',
                },
                {
                    path: 'resources/js/lib/pricing.js',
                    lineCount: 39,
                    role: 'Client mirror of the pricing estimate, used only for the live studio preview.',
                    details: ['Server recomputes authoritatively on submit; the markup is always rounded up.'],
                },
                {
                    path: 'resources/js/lib/http.js',
                    lineCount: 23,
                    role: '`fetch` wrapper with JSON headers + same-origin credentials, and a UUID helper with fallback.',
                },
            ],
        },
        {
            id: 'styles',
            title: 'Styles',
            summary: 'Tailwind v4 CSS-first setup: tokens become utilities, then base styles, utilities and animations.',
            entries: [
                { path: 'resources/css/app.css', lineCount: 6, role: 'Entry: imports Tailwind, tokens, base, utilities, animations and defines the `dark` custom variant.' },
                { path: 'resources/css/tokens.css', lineCount: 69, role: '`@theme inline` brand scale, fonts, surfaces, semantic colours, shadows; `:root` and `.dark` values.' },
                { path: 'resources/css/base.css', lineCount: 65, role: 'Element defaults: body colours, heading font, selection, focus ring, scrollbars, reduced-motion baseline.' },
                { path: 'resources/css/utilities.css', lineCount: 74, role: 'Composable classes: `text-gradient`, `surface`, `ambient`, `hairline`, `glass`, `lift`, `clamp-*`, `tabular`, `orb`, `dot-grid`.' },
                { path: 'resources/css/animations.css', lineCount: 126, role: 'Keyframes and helpers: rise, fade, scale-in, slide-in, shimmer, pulse-ring, float, spin-slow, stagger; disables motion when requested.' },
            ],
        },
        {
            id: 'entrypoints',
            title: 'Entrypoints & views',
            summary: 'How the SPA boots inside the Blade document.',
            entries: [
                {
                    path: 'resources/js/app.js',
                    lineCount: 27,
                    role: 'Creates the Inertia app, glob-resolves pages, initialises theme before mount, sets the progress bar colour.',
                    details: [
                        '`title` deliberately returns empty for an empty title so the server-rendered `<title>` is not overwritten on boot.',
                        'Throws a clear error when a page name does not exist.',
                    ],
                },
                {
                    path: 'public/js/theme-init.js',
                    lineCount: 14,
                    role: 'Inline-blocking pre-Vue script that applies the stored/system theme before first paint (no flash).',
                },
                {
                    path: 'resources/views/app.blade.php',
                    lineCount: 60,
                    role: 'Root document: server-rendered SEO/OG tags, canonical, robots, favicon/manifest, Bunny fonts, theme-init, Vite, JSON-LD, noscript fallback.',
                    details: ['Reads `$page[\'props\'][\'seo\']` so crawlers see real metadata before JavaScript.'],
                },
                { path: 'resources/views/sitemap.blade.php', lineCount: 11, role: 'XML template for the sitemap controller.' },
                { path: 'resources/views/welcome.blade.php', lineCount: 215, role: 'Default Laravel welcome view; not routed, kept only as framework scaffolding.' },
            ],
        },
    ],

    data: [
        {
            id: 'migrations',
            title: 'Migrations',
            summary: 'Schema history. The Lumora tables are the five dated migrations; the three earliest are framework defaults.',
            entries: [
                { path: 'database/migrations/0001_01_01_000000_create_users_table.php', lineCount: 44, role: 'Framework users, password reset tokens and sessions tables.' },
                { path: 'database/migrations/0001_01_01_000001_create_cache_table.php', lineCount: 31, role: 'Framework cache and cache locks.' },
                { path: 'database/migrations/0001_01_01_000002_create_jobs_table.php', lineCount: 53, role: 'Framework jobs, job batches and failed jobs (the database queue).' },
                {
                    path: 'database/migrations/2026_11_18_000001_add_lumora_columns_to_users_table.php',
                    lineCount: 21,
                    role: 'Adds `is_admin` (indexed), `credit_balance_micros` and `last_login_at`.',
                },
                {
                    path: 'database/migrations/2026_11_18_000002_create_generations_table.php',
                    lineCount: 51,
                    role: 'Generations: UUID, prompt, canvas, status, provider ids, error, image URLs, estimated/actual costs, markup, usage, meta, lifecycle timestamps, soft deletes.',
                    details: [
                        'Indexes: `(user_id, created_at)`, `(user_id, status)`, `(status, updated_at)`; unique `uuid` and `provider_generation_id`.',
                    ],
                },
                {
                    path: 'database/migrations/2026_11_18_000003_create_credit_transactions_table.php',
                    lineCount: 30,
                    role: 'Ledger: signed amount, balance after, unique reference, description, meta.',
                    details: ['`unique(generation_id, type)` is the database-level exactly-once guarantee.'],
                },
                {
                    path: 'database/migrations/2026_11_18_000004_create_webhook_events_table.php',
                    lineCount: 30,
                    role: 'Webhook inbox: unique provider event id, type/status, links, signature flag, processed/ignored, raw payload, headers, error.',
                },
                {
                    path: 'database/migrations/2026_11_18_000005_create_provider_usage_records_table.php',
                    lineCount: 28,
                    role: 'Reconciled provider usage: unique usage id, provider generation link, token buckets, cost, occurred_at, raw JSON.',
                },
            ],
        },
        {
            id: 'seeders',
            title: 'Factories & seeders',
            summary: 'Demo data for local exploration and test fixtures.',
            entries: [
                { path: 'database/factories/UserFactory.php', lineCount: 40, role: 'User factory with admin/bonus state helpers (see the file for the exact states).' },
                { path: 'database/factories/GenerationFactory.php', lineCount: 61, role: 'Generation factory with sensible canvas/cost defaults and status states.' },
                { path: 'database/seeders/DatabaseSeeder.php', lineCount: 17, role: 'Creates the administrator, then seeds demo data outside production only.' },
                {
                    path: 'database/seeders/AdminUserSeeder.php',
                    lineCount: 26,
                    role: 'Creates or refreshes the admin account from `LUMORA_ADMIN_NAME`, `LUMORA_ADMIN_EMAIL` and `LUMORA_ADMIN_PASSWORD`.',
                    details: ['Safe to re-run; the password is reset to the configured value.'],
                },
                {
                    path: 'database/seeders/DemoDataSeeder.php',
                    lineCount: 215,
                    role: 'Creates the demo creator (`demo@lumora.app`, password `password`), sample creations and ledger history, and funds the admin when present.',
                    details: ['Run with `php artisan migrate:fresh --seed` locally only.'],
                },
            ],
        },
        {
            id: 'lumora-config',
            title: 'config/lumora.php',
            summary: 'The product\'s single configuration surface. Every key below is read directly by services or controllers.',
            entries: [
                {
                    path: 'brand',
                    role: 'Name, tagline, support email. Shared with every Inertia page.',
                },
                {
                    path: 'seo',
                    role: 'Default title/description/keywords and Twitter handle; used by the Blade document and `Seo`.',
                },
                {
                    path: 'credits',
                    role: '`micros_per_credit` (10,000), welcome bonus (default $5 / 500 credits) and the Starter/Studio/Pro packages.',
                    details: ['Package credits are the grant amount; `price_micros` is display/demo pricing only.'],
                },
                {
                    path: 'generation',
                    role: 'Active limit, poll interval/attempts, stale timeout, `after_response`, allowed canvases, qualities, defaults, mirroring settings.',
                    details: ['Canvas values must match the provider exactly: 1024x1024, 1024x1536, 1536x1024.'],
                },
                {
                    path: 'provider',
                    role: 'Pixelforge base URL, credentials, timeouts, rate limit, endpoint paths, signature header/scheme/tolerance.',
                    details: ['Base URL is origin-only; paths include `/v1`. Secrets come from `.env`.'],
                },
                {
                    path: 'pricing',
                    role: 'Markup basis points, per-million token rates, tokenisation assumptions, output-token table, reasoning maxima, fallback cost.',
                    details: ['Changing any number here changes both estimates and settlements; tests pin the math.'],
                },
            ],
        },
        {
            id: 'framework-config',
            title: 'Framework config',
            summary: 'Standard Laravel files; only note the values this app actually relies on.',
            entries: [
                { path: 'config/app.php', lineCount: 104, role: 'App name, env, key, debug, URL, locale, timezone.' },
                { path: 'config/auth.php', lineCount: 103, role: 'Session guard with the `users` provider (Eloquent). No API guard.' },
                { path: 'config/cache.php', lineCount: 115, role: 'Default cache store; database driver locally, array in tests.' },
                { path: 'config/database.php', lineCount: 162, role: 'SQLite locally by default; the queue and cache tables use the same connection.' },
                { path: 'config/filesystems.php', lineCount: 68, role: '`public` disk serves mirrored renders under `/storage`; `local` for app files.' },
                { path: 'config/inertia.php', lineCount: 27, role: 'Inertia testing/SSR switches; SSR is not enabled.' },
                { path: 'config/logging.php', lineCount: 119, role: 'Default stack (single/daily); provider and settlement events log at info/warning.' },
                { path: 'config/mail.php', lineCount: 100, role: 'Mail transport; password reset needs a real transport in production.' },
                { path: 'config/queue.php', lineCount: 110, role: 'Database queue is the local/production default; `sync` in tests.' },
                { path: 'config/services.php', lineCount: 31, role: 'Third-party service credentials container (not used by Pixelforge, which reads `lumora.provider`).' },
                { path: 'config/session.php', lineCount: 197, role: 'File sessions locally, array in tests; cookie and lifetime settings.' },
            ],
        },
        {
            id: 'tooling',
            title: 'Tooling',
            summary: 'Build, test and formatting configuration.',
            entries: [
                {
                    path: 'vite.config.js',
                    lineCount: 33,
                    role: 'Laravel plugin (CSS + JS entries), Vue plugin, Tailwind v4 plugin, `@` → `resources/js` alias, ignores compiled views.',
                },
                {
                    path: 'package.json',
                    role: 'Scripts `dev` and `build`; Vue 3.5, Inertia Vue 3, Tailwind 4, Vite 8, lucide icons, Laravel Vite plugin.',
                },
                {
                    path: 'composer.json',
                    role: 'PHP 8.3, Laravel 13, Inertia Laravel 3, Tinker; dev: PHPUnit 12, Pint, Faker, Boost, Pail.',
                },
                {
                    path: 'phpunit.xml',
                    lineCount: 37,
                    role: 'In-memory SQLite, array cache/session, sync queue, array mail, mirroring disabled. This is why tests never hit the network.',
                },
            ],
        },
        {
            id: 'environment',
            title: 'Environment variables',
            summary: 'Only the values that change behaviour. See `.env.example` for the full annotated list.',
            entries: [
                { path: 'APP_URL', role: 'Used for canonicals, sitemap and signed URLs. Locally it points at the Herd/ngrok host.' },
                { path: 'DB_*', role: 'SQLite by default; set MySQL/Postgres credentials for production.' },
                { path: 'QUEUE_CONNECTION', role: '`database` locally and in production; `sync` in tests. Without a worker nothing is submitted.' },
                { path: 'PIXELFORGE_BASE_URL / API_KEY / WEBHOOK_SECRET', role: 'Provider origin, bearer token and HMAC secret. Never commit real values.' },
                { path: 'PIXELFORGE_*_PATH / HEADER / SCHEME / TOLERANCE', role: 'Protocol switches so the integration can follow the provider spec without code changes.' },
                { path: 'LUMORA_MARKUP_BASIS_POINTS', role: 'Customer markup; 2500 = 25%.' },
                { path: 'LUMORA_WELCOME_BONUS_MICROS', role: 'Signup grant in micros; default 5,000,000 ($5).' },
                { path: 'LUMORA_MAX_ACTIVE_GENERATIONS', role: 'Concurrent renders per user; default 3.' },
                { path: 'LUMORA_MIRROR_IMAGES / IMAGE_DISK', role: 'Copy finished renders locally; keep enabled in production because provider URLs expire.' },
                { path: 'LUMORA_STRIP_CAPTIONS', role: 'Default for generations without a stored preference. Per-render control lives in the studio checkbox; when on, the provider caption is painted out of mirrored PNGs and the PIXELFORGE watermark is kept.' },
            ],
        },
        {
            id: 'runtime',
            title: 'Runtime pieces',
            summary: 'Things that must exist outside the code for the app to behave.',
            entries: [
                {
                    path: 'storage link',
                    role: '`php artisan storage:link` exposes `storage/app/public` at `/storage`. Without it, mirrored images 404.',
                },
                {
                    path: 'queue worker',
                    role: '`php artisan queue:work` processes submit, poll and mirror jobs. Workers cache code, so restart after deploys (`queue:restart`).',
                },
                {
                    path: 'scheduler',
                    role: '`php artisan schedule:work` (or cron `schedule:run`) drives polling, usage reconciliation and stale expiry.',
                },
                {
                    path: 'webhook tunnel',
                    role: 'The provider must reach `POST /webhooks/pixelforge`. Locally that means a tunnel (ngrok) and `APP_URL` matching it.',
                },
            ],
        },
    ],

    flows: [
        {
            id: 'render-lifecycle',
            title: 'Render lifecycle',
            summary: 'The happy path from a prompt to a mirrored image and a settled ledger.',
            entries: [
                {
                    path: 'prompt → credits → image',
                    role: 'Nineteen steps, three fallbacks, one settlement.',
                    steps: [
                        '`Generate.vue` validates locally and shows an estimate from `estimatePrice()`.',
                        '`POST /generations` hits `StoreGenerationRequest`, then `GenerationService::create()`.',
                        '`PricingCalculator::estimate()` computes provider cost + markup; the user row is locked.',
                        'The generation row is created as `pending`, then `CreditService::holdForGeneration()` debits the estimate.',
                        '`GenerationService::dispatch()` queues `SubmitGenerationJob`.',
                        'The job rate-limits, builds the documented payload and calls `createGeneration()` with `Idempotency-Key: lumora-generation-{uuid}`.',
                        'The provider returns a generation id; the job marks the row `processing` and queues `PollGenerationJob` after 5s.',
                        'The provider sends a signed webhook. The controller verifies HMAC, dedupes by event id and calls `WebhookProcessor`.',
                        'The processor finds the row by provider id or `metadata.generation_uuid`, checks ordering, normalises status.',
                        'On success, `GenerationFinalizer::succeed()` prices actual usage and calls `CreditService::settleGeneration()`.',
                        'The difference between hold and actual charge is refunded (usually) or debited, then the row is `succeeded` and `settled_at` is set.',
                        '`MirrorGenerationImage` downloads the render to `storage/app/public/generations/{uuid}.{ext}` after commit.',
                        '`useGenerationPolling()` sees `is_terminal`, stops, and refreshes the balance; the webhook remains the primary signal.',
                        'Late, duplicate or out-of-order webhooks become `duplicate` / `ignored` events and move no money.',
                    ],
                },
                {
                    path: 'failure → refund',
                    role: 'Every terminal failure path ends in the same finalizer.',
                    steps: [
                        'Provider rejects the submit with a permanent 4xx: fail immediately and refund.',
                        'Transient 404/429/5xx/network: retry with backoff using the same idempotency key; only fail after the last attempt.',
                        'Provider reports `failed` by webhook or poll: refund, keep provider cost if reported, store the error.',
                        'No provider id, too many polls, or untouched past the stale timeout: fail and refund.',
                        'Any manual reconcile replay uses the same idempotent guards, so a retry can never double-charge.',
                    ],
                },
            ],
        },
        {
            id: 'webhook-security',
            title: 'Webhook security & ordering',
            summary: 'Signed, replayable and safe against duplicate or late delivery.',
            entries: [
                {
                    path: 'verification',
                    role: 'How a delivery is proven authentic.',
                    steps: [
                        'Read the raw body before any parsing.',
                        'Find the signature header: configured name first, then any header containing `sig`.',
                        'Parse `t=...,v1=...` (or a bare signature) and compute HMAC-SHA256 over `{timestamp}.{body}`.',
                        'Reject timestamps outside the 300-second tolerance; compare with `hash_equals`.',
                        'Any candidate signature matching is accepted; otherwise return 401 without storing the event.',
                    ],
                },
                {
                    path: 'idempotency',
                    role: 'Why the same event twice is harmless.',
                    steps: [
                        'Event id comes from `id`, `event_id`, `data.event_id` or `event.id`, else a SHA-256 of the body.',
                        '`webhook_events.provider_event_id` is unique; an event already marked processed returns `duplicate`.',
                        'The finalizer additionally refuses to settle a row twice (`settled_at` guard).',
                        'The ledger’s `unique(generation_id, type)` is the last line of defence.',
                        'Out-of-order events are compared against `last_provider_event_at` and ignored when older.',
                    ],
                },
            ],
        },
        {
            id: 'wallet',
            title: 'Wallet & settlement math',
            summary: 'A worked example of the three possible settlement outcomes.',
            entries: [
                {
                    path: 'hold 86,513 → charge 67,125',
                    role: 'Typical success: the unused hold is returned.',
                    steps: [
                        'Hold row: `-86,513` (estimated provider 53,700 + 25% rounded up).',
                        'Actual provider cost from payload usage: 53,700.',
                        'Actual charge: `withMarkup(53,700, 2500)` = 67,125.',
                        'Settlement row: `+19,388` because the actual charge is lower than the hold.',
                        'Net charged: 67,125; balance and ledger sum stay equal.',
                    ],
                },
                {
                    path: 'charge > hold',
                    role: 'If the provider used more tokens than estimated, the difference is debited.',
                    details: [
                        'The debit is capped at the available balance so the wallet never goes negative.',
                        'Any unpaid remainder is stored as `shortfall_micros` on the settlement row and generation meta for review.',
                    ],
                },
                {
                    path: 'failure',
                    role: 'The full hold is returned exactly once.',
                    details: [
                        'A `refund` row equal to the hold is written; the customer charge is zero.',
                        'Provider cost, if reported, is still recorded for admin reconciliation.',
                    ],
                },
            ],
        },
        {
            id: 'failure-modes',
            title: 'Failure modes cheat sheet',
            summary: 'What the code does for each provider/network condition.',
            entries: [
                {
                    path: '404 not_found on create',
                    role: 'Treated as transient (provider returns it while a resource propagates). Retried with the same idempotency key, then refunded if exhausted.',
                },
                {
                    path: '429 rate limited',
                    role: '`Retry-After` respected; job released. Submission also has a local per-minute limiter.',
                },
                {
                    path: '5xx / connection failure',
                    role: 'Retry with backoff; exhausted submit fails with `provider_unavailable` and refunds.',
                },
                {
                    path: '422 validation',
                    role: 'Permanent: fail immediately with the provider message, refund, store `meta.provider_error`.',
                },
                {
                    path: 'webhook duplicate / late',
                    role: 'Recorded as `duplicate` or ignored as `out_of_order`; no money moves.',
                },
                {
                    path: 'image mirror fails',
                    role: 'Warning logged; the provider URL remains valid for 24 hours. Re-running the job can still copy it.',
                },
                {
                    path: 'no worker running',
                    role: 'Rows stay `pending` with held credits until a worker processes the queue or expiry refunds them.',
                },
            ],
        },
        {
            id: 'tests',
            title: 'Test suite map',
            summary: 'PHPUnit feature tests boot the app against in-memory SQLite; unit tests cover pure math and signatures.',
            entries: [
                { path: 'tests/Unit/MoneyTest.php', lineCount: 50, role: 'Micro-dollar conversions, formatting and markup rounding.' },
                { path: 'tests/Unit/WebhookSignatureTest.php', lineCount: 94, role: 'Timestamped/raw signatures, tolerance, multiple candidates, header parsing.' },
                { path: 'tests/Feature/GenerationFlowTest.php', lineCount: 249, role: 'Create → hold → settle, failure refunds, limits, ownership, status payload, expiry.' },
                { path: 'tests/Feature/SubmitGenerationTest.php', lineCount: 119, role: '404 retry then success, provider rejection capture, exhausted retries refund.' },
                { path: 'tests/Feature/WebhookTest.php', lineCount: 309, role: 'Valid/invalid signatures, duplicates, out-of-order, unmatched, ping, replay, malformed bodies.' },
                { path: 'tests/Feature/PricingTest.php', lineCount: 89, role: 'Estimates by quality/size, explicit cost precedence, token rates, markup rounding.' },
                { path: 'tests/Feature/ImageMirrorTest.php', lineCount: 53, role: 'Mirror success, local URL rewrite, skip conditions, size/type handling.' },
                { path: 'tests/Feature/BillingTest.php', lineCount: 81, role: 'Purchases, idempotent references, summary numbers, ledger display.' },
                { path: 'tests/Feature/AdminTest.php', lineCount: 125, role: 'Admin access, metrics totals/profit, reconciliation sync and error flash.' },
                { path: 'tests/Feature/AuthorizationTest.php', lineCount: 102, role: 'Guest redirects, cross-user view/delete prevention, admin-only routes.' },
                { path: 'tests/Feature/RegistrationTest.php', lineCount: 79, role: 'Signup validation, welcome grant, login, rate limiting.' },
                { path: 'tests/Feature/SettingsTest.php', lineCount: 89, role: 'Profile/password updates and account deletion guards.' },
                { path: 'tests/Feature/DocumentationTest.php', lineCount: 47, role: 'Public guides render, are indexable, the redirect works, sitemap contains public pages.' },
                { path: 'tests/TestCase.php', lineCount: 7, role: 'Base test case; no global helpers.' },
            ],
        },
        {
            id: 'commands',
            title: 'Command reference',
            summary: 'The commands used day to day.',
            entries: [
                { path: 'php artisan test --compact', role: 'Run the full suite. Pass a file path or `--filter=` to narrow it.' },
                { path: 'vendor/bin/pint --dirty --format agent', role: 'Format changed PHP files before finishing a task.' },
                { path: 'npm run build', role: 'Production Vite build. Run after CSS/JS changes or when the manifest is stale.' },
                { path: 'npm run dev', role: 'Vite dev server with HMR (use alongside the queue worker).' },
                { path: 'php artisan queue:work', role: 'Process submit/poll/mirror jobs. Restart after code changes.' },
                { path: 'php artisan schedule:work', role: 'Run the scheduler locally (poll, reconcile, expire).' },
                { path: 'php artisan lumora:reconcile-usage --days=2', role: 'Manual usage sync + webhook replay, also exposed as an admin button.' },
                { path: 'php artisan storage:link', role: 'Required once so mirrored images are publicly served.' },
            ],
        },
    ],
};
