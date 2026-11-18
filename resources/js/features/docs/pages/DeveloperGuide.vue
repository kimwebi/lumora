<script setup>
import DocLayout from '@/features/docs/components/DocLayout.vue';

const sections = [
    {
        id: 'architecture',
        title: 'Architecture at a glance',
        intro: 'Lumora is a Laravel application with a Vue single-page front end delivered through Inertia. There is no separate API server to run or deploy: routes, validation, authorization and database work happen in Laravel, and Inertia renders Vue pages with server-supplied props.',
        blocks: [
            {
                type: 'table',
                head: ['Layer', 'Technology', 'Where it lives'],
                rows: [
                    ['Backend', 'Laravel 13, PHP 8.3+', '`app/`, `routes/`, `config/`'],
                    ['Front end', 'Vue 3 Composition API, Inertia v3', '`resources/js/`'],
                    ['Styling', 'Tailwind CSS v4', '`resources/css/`'],
                    ['Database', 'MySQL in development/production, SQLite in tests', '`database/`'],
                    ['Queue, cache, sessions', 'Database drivers by default', '`.env`'],
                    ['Icons and fonts', '`lucide-vue-next`, Shantell Sans, Outfit, Quicksand', '`resources/css/tokens.css`'],
                ],
            },
            {
                type: 'paragraph',
                text: 'A typical page request flows through four steps:',
            },
            {
                type: 'list',
                ordered: true,
                items: [
                    'A browser request hits a route in `routes/web.php`.',
                    'The controller validates input, queries models and returns `Inertia::render(\'features/.../pages/...\')` with props.',
                    'Shared props such as the signed-in user, flash messages, notifications and SEO metadata come from `app/Http/Middleware/HandleInertiaRequests.php`.',
                    'The matching Vue page renders inside a layout. Later navigations are handled client-side by Inertia, while SEO tags are kept in sync by `useSeo()`.',
                ],
            },
            {
                type: 'callout',
                tone: 'info',
                title: 'No SSR',
                text: 'Lumora does not run a Node server in production. SEO tags are written into the root Blade document on the first request (`resources/views/app.blade.php`) and updated on the client after Inertia navigation.',
            },
        ],
    },
    {
        id: 'structure',
        title: 'Project map',
        intro: 'The codebase is organised by responsibility on the server and by feature on the client.',
        blocks: [
            {
                type: 'code',
                language: 'text',
                code: `app/
  Console/Commands/            lumora:poll-generations, lumora:expire-generations, lumora:reconcile-usage, lumora:strip-image-captions
  Http/Controllers/            pages, auth, billing, settings, webhooks, admin
  Http/Middleware/             Inertia shared props, admin gate
  Http/Requests/               validation for generations, purchases, profile, password
  Http/Resources/              JSON shape for generations and status polling
  Jobs/                        SubmitGenerationJob, PollGenerationJob
  Models/                      User, Generation, CreditTransaction, WebhookEvent, ProviderUsageRecord
  Policies/                    GenerationPolicy
  Services/
    Admin/AdminMetrics.php
    Billing/CreditService.php
    Generations/               GenerationService, GenerationFinalizer, WebhookProcessor
    Pixelforge/                PixelforgeClient, PricingCalculator, WebhookSignature, UsageReconciler
  Support/                     Money, Seo
config/lumora.php              brand, SEO, credits, generation options, provider, pricing
database/                      migrations, factories, seeders
resources/js/
  components/{ui,brand,layout,charts}/
  composables/                 theme, toast, seo, polling, sidebar, clipboard, click-outside
  features/<feature>/pages/    Inertia pages, grouped by feature
  features/docs/               this guide and the user guide
  layouts/                     AppLayout, AuthLayout
  lib/                         formatting, routes, pricing helper, HTTP helpers
resources/css/                 Tailwind entry, design tokens, base styles, utilities
routes/web.php                 public, auth, app and admin routes
tests/Unit, tests/Feature      PHPUnit test suite`,
            },
            {
                type: 'table',
                head: ['Path', 'Responsibility'],
                rows: [
                    ['`routes/web.php`', 'Every HTTP route, including the webhook, sitemap, robots and documentation pages.'],
                    ['`config/lumora.php`', 'Single source of truth for brand, credits, packages, canvas options, provider endpoints and pricing.'],
                    ['`app/Services/Billing/CreditService.php`', 'All wallet mutations: holds, settlements, refunds, purchases and grants.'],
                    ['`app/Services/Generations/GenerationService.php`', 'Creates a generation, checks limits, applies the hold, dispatches the job and exposes status.'],
                    ['`app/Services/Generations/GenerationFinalizer.php`', 'The only place a render becomes terminal. Settles or refunds exactly once.'],
                    ['`app/Services/Pixelforge/*`', 'Provider HTTP client, pricing maths, webhook signature verification and usage reconciliation.'],
                    ['`resources/js/features/*/pages/*.vue`', 'One Inertia page per screen, grouped by product area.'],
                    ['`tests/`', 'Unit tests for money and signatures; feature tests for generation, webhooks, billing, admin, auth and settings.'],
                ],
            },
        ],
    },
    {
        id: 'local-setup',
        title: 'Run Lumora locally',
        intro: 'You need PHP 8.3+, Composer, Node.js 20+ and a database. MySQL is used locally here; tests run on in-memory SQLite.',
        blocks: [
            {
                type: 'steps',
                items: [
                    { title: 'Install PHP dependencies', text: 'Run `composer install` in the project root.' },
                    { title: 'Create the environment file', text: 'Copy `.env.example` to `.env`. On PowerShell use `Copy-Item .env.example .env`; on macOS/Linux use `cp .env.example .env`.' },
                    { title: 'Generate the application key', text: 'Run `php artisan key:generate`.' },
                    { title: 'Configure the database', text: 'Set `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME` and `DB_PASSWORD` in `.env`.' },
                    { title: 'Migrate and seed', text: 'Run `php artisan migrate --seed`. This creates the schema, demo users and demo generations.' },
                    { title: 'Install and build the front end', text: 'Run `npm install` then `npm run build`. During front-end work use `npm run dev` instead.' },
                    { title: 'Start the app', text: 'With Laravel Herd the project is served at `https://<project>.test`. Otherwise run `php artisan serve`.' },
                    { title: 'Start the worker and scheduler', text: 'In separate terminals run `php artisan queue:work` and `php artisan schedule:work`. Without a worker, generations stay queued; without the scheduler, polling and expiry do not run.' },
                ],
            },
            {
                type: 'table',
                head: ['Role', 'Email', 'Password'],
                rows: [
                    ['Admin', '`admin@lumora.app`', '`password`'],
                    ['Creator', '`demo@lumora.app`', '`password`'],
                ],
            },
            {
                type: 'callout',
                tone: 'warning',
                title: 'Queue driver',
                text: '`.env.example` sets `QUEUE_CONNECTION=database`. A running `queue:work` process is required. If you change queue configuration, restart the worker.',
            },
        ],
    },
    {
        id: 'configuration',
        title: 'Configuration reference',
        intro: 'Application-specific values live in `config/lumora.php` and are read from `LUMORA_*` and `PIXELFORGE_*` environment variables. Nothing provider-specific is hardcoded in controllers or services.',
        blocks: [
            {
                type: 'code',
                language: 'dotenv',
                code: `# Brand and credits
LUMORA_SUPPORT_EMAIL=hello@lumora.app
LUMORA_TWITTER_HANDLE=@lumoraapp
LUMORA_WELCOME_BONUS_MICROS=5000000
LUMORA_MARKUP_BASIS_POINTS=2500

# Generation behaviour
LUMORA_MAX_ACTIVE_GENERATIONS=3
LUMORA_STALE_GENERATION_MINUTES=20
LUMORA_MAX_POLL_ATTEMPTS=60
LUMORA_GENERATIONS_AFTER_RESPONSE=false`,
            },
            {
                type: 'table',
                head: ['Variable', 'Default', 'Effect'],
                rows: [
                    ['`LUMORA_WELCOME_BONUS_MICROS`', '`5000000`', 'Welcome credit grant. 5,000,000 micros = US$5 = 500 credits.'],
                    ['`LUMORA_MARKUP_BASIS_POINTS`', '`2500`', 'Platform markup. 2500 basis points = 25%.'],
                    ['`LUMORA_MAX_ACTIVE_GENERATIONS`', '`3`', 'Concurrent renders allowed per user.'],
                    ['`LUMORA_STALE_GENERATION_MINUTES`', '`20`', 'Age after which an unfinished render is failed and refunded.'],
                    ['`LUMORA_MAX_POLL_ATTEMPTS`', '`60`', 'Maximum status polls before a render is treated as stale.'],
                    ['`LUMORA_GENERATIONS_AFTER_RESPONSE`', '`false`', 'When true, dispatch the submit job after the response instead of during the request. Useful on hosts without a worker during local debugging.'],
                    ['`LUMORA_MIRROR_IMAGES`', '`true`', 'Copy each finished render onto the app disk, because signed provider image URLs expire 24 hours after completion.'],
                    ['`LUMORA_IMAGE_DISK`', '`public`', 'Filesystem disk used for mirrored renders. Run `php artisan storage:link` so `/storage/generations` is publicly served.'],
                    ['`LUMORA_STRIP_CAPTIONS`', '`false`', 'Default for renders without a stored preference. The studio checkbox (Remove provider caption) controls each render; when on, the prompt, generation id and quality are painted out of the mirrored PNG while the PIXELFORGE watermark is kept. Existing images can be cleaned with `php artisan lumora:strip-image-captions`.'],
                ],
            },
            {
                type: 'paragraph',
                text: 'Money and pricing are configured in `config/lumora.php` rather than the environment: token rates (`pricing.usage_rates_micros_per_million`), documented output-token estimates (`pricing.output_token_estimates`), reasoning budgets (`pricing.reasoning_token_max`), credit packages (`credits.packages`) and canvas options (`generation.size_options`). Changing rates updates the studio estimate automatically because they are sent to the page as props.',
            },
            {
                type: 'callout',
                tone: 'warning',
                title: 'After editing .env',
                text: 'Run `php artisan config:clear` in development. In production run `php artisan config:cache` after every environment change, then `php artisan queue:restart` so workers pick up the new values.',
            },
        ],
    },
    {
        id: 'api-keys',
        title: 'Connect Pixelforge with real keys',
        intro: 'The integration is driven entirely by environment values. You can wire up live credentials without touching application code, provided the provider follows the paths and signature scheme configured below.',
        blocks: [
            {
                type: 'steps',
                items: [
                    { title: 'Collect the credentials', text: 'In the Pixelforge dashboard, copy the API base URL, an API key with permission to create generations, and the webhook signing secret.' },
                    { title: 'Add them to .env', text: 'Use the block below. Keep the values server-side only; they are never exposed to Inertia props or the browser.' },
                    { title: 'Match the provider contract', text: 'Compare `PIXELFORGE_GENERATIONS_PATH`, `PIXELFORGE_USAGE_PATH`, header names and signature scheme with the official Pixelforge documentation. Override the environment values if they differ.' },
                    { title: 'Clear cached configuration', text: 'Run `php artisan config:clear` locally, or `php artisan config:cache` in production.' },
                    { title: 'Restart workers', text: 'Run `php artisan queue:restart` whenever credentials or configuration change. Long-running workers keep the old config in memory.' },
                    { title: 'Verify the connection', text: 'Open **Generate Image**. The provider badge should report that the studio is connected and the generate button should become available when the other conditions are met.' },
                    { title: 'Send a real render', text: 'Submit a low quality render and watch `storage/logs/laravel.log`. The client logs the method, path, status, duration and provider request id for every call.' },
                    { title: 'Reconcile usage', text: 'Once real jobs have run, use **Admin Dashboard → Reconcile usage** (or `php artisan lumora:reconcile-usage --days=2`) to pull `GET /v1/usage` and compare provider cost with local settlements.' },
                ],
            },
            {
                type: 'code',
                language: 'dotenv',
                code: `PIXELFORGE_BASE_URL=https://pixelforge.promptedit.dev
PIXELFORGE_API_KEY=your_api_key_here
PIXELFORGE_WEBHOOK_SECRET=your_webhook_signing_secret

PIXELFORGE_TIMEOUT=30
PIXELFORGE_CONNECT_TIMEOUT=10
PIXELFORGE_REQUESTS_PER_MINUTE=60

PIXELFORGE_GENERATIONS_PATH=/v1/generations
PIXELFORGE_USAGE_PATH=/v1/usage

PIXELFORGE_SIGNATURE_HEADER=Pixelforge-Signature
PIXELFORGE_TIMESTAMP_HEADER=X-Pixelforge-Timestamp
PIXELFORGE_SIGNATURE_TOLERANCE=300
PIXELFORGE_SIGNATURE_SCHEME=timestamped`,
            },
            {
                type: 'table',
                head: ['Setting', 'Used for'],
                rows: [
                    ['`PIXELFORGE_BASE_URL`', 'Provider origin only, for example `https://pixelforge.promptedit.dev`. Do not append `/v1`; the paths below already include it. Trailing slashes are normalised.'],
                    ['`PIXELFORGE_API_KEY`', 'Sent as a Bearer token and used to decide whether the studio is enabled.'],
                    ['`PIXELFORGE_WEBHOOK_SECRET`', 'HMAC key for incoming webhook verification.'],
                    ['`PIXELFORGE_GENERATIONS_PATH`', 'Create and fetch generation endpoint. The provider id is appended for polling.'],
                    ['`PIXELFORGE_USAGE_PATH`', 'Usage endpoint read by the reconciliation command and admin action.'],
                    ['`PIXELFORGE_SIGNATURE_*`', 'Header names, clock tolerance and signing scheme for webhooks.'],
                    ['`PIXELFORGE_REQUESTS_PER_MINUTE`', 'Local rate limit shared by submit jobs before calling the provider.'],
                ],
            },
            {
                type: 'paragraph',
                text: 'The submit job sends a JSON body with `prompt`, `size`, `quality`, `reference_images` when present, and `metadata.generation_uuid`. Sizes are `1024x1024`, `1536x1024` and `1024x1536`; qualities are `low`, `medium` and `high`. Every metadata value is sent as a string because the provider rejects numbers. The provider rejects unknown fields too, so no other parameters are sent. It also sends an `Idempotency-Key` of `lumora-generation-{uuid}` so a retried submit cannot create a duplicate render.',
            },
            {
                type: 'code',
                language: 'json',
                code: `{
  "prompt": "A cinematic still of a glass whale drifting through a violet nebula",
  "size": "1024x1024",
  "quality": "medium",
  "reference_images": ["https://example.com/reference.jpg"],
  "metadata": {
    "generation_uuid": "0d9f...",
    "lumora_user_id": "42"
  }
}`,
            },
            {
                type: 'callout',
                tone: 'danger',
                title: 'Never commit secrets',
                text: '`.env` is gitignored and must never be committed or pasted into a public issue. In production set these values in your host’s secret manager or environment panel. Rotate the API key and webhook secret immediately if either is exposed.',
            },
            {
                type: 'callout',
                tone: 'info',
                title: 'If the official contract differs',
                text: 'Change environment values first. If Pixelforge wraps payloads or returns a different shape, the two files to adapt are `app/Services/Pixelforge/PixelforgeClient.php` (outbound) and `app/Services/Generations/WebhookProcessor.php` plus `GenerationFinalizer` (inbound). Do not guess behaviour; match the published specification.',
            },
        ],
    },
    {
        id: 'webhooks',
        title: 'Webhook setup and testing',
        intro: 'Webhooks are the fast path for settling renders. Polling is a fallback, not a replacement: configure webhooks for reliable, near-real-time results.',
        blocks: [
            {
                type: 'steps',
                items: [
                    { title: 'Expose the endpoint publicly', text: 'The route is `POST /webhooks/pixelforge` and is CSRF-exempt. Providers cannot reach a local `.test` domain. Use a tunnel such as Herd share, ngrok or Expose while testing, or a real HTTPS domain in production.' },
                    { title: 'Register the URL with Pixelforge', text: 'Use `https://your-domain.com/webhooks/pixelforge` in the provider dashboard and select the generation completion and failure events.' },
                    { title: 'Use the same signing secret', text: 'Set `PIXELFORGE_WEBHOOK_SECRET` to the secret Pixelforge uses to sign deliveries, then clear the config cache and restart workers.' },
                    { title: 'Send a test delivery', text: 'Use the signed `curl` example below, or the provider’s “send test webhook” button. A `200` response with `received: true` means the signature and payload were accepted.' },
                    { title: 'Check the event log', text: 'Every delivery is stored in `webhook_events` with its type, status, provider generation id, raw payload, headers and outcome. Inspect it when a render does not settle.' },
                ],
            },
            {
                type: 'table',
                head: ['Scheme', 'Signature input', 'Header formats accepted'],
                rows: [
                    ['`timestamped` (default)', '`HMAC-SHA256("{timestamp}.{rawBody}")`', 'Raw hex digest or Stripe-style `t=...,v1=...`; timestamp from the configured header or any `*timestamp*` header.'],
                    ['`raw`', '`HMAC-SHA256(rawBody)`', 'Raw hex digest; no timestamp is required.'],
                ],
            },
            {
                type: 'paragraph',
                text: 'The verifier reads `PIXELFORGE_SIGNATURE_HEADER` first, then any other header whose name contains `sig` (for example `X-Webhook-Signature`), so a provider header rename does not require a code change. When no timestamp accompanies a delivery it verifies the raw body. Missing secrets, missing signatures and timestamps older than `PIXELFORGE_SIGNATURE_TOLERANCE` seconds (300 by default) are rejected. A failed check returns HTTP 401; malformed JSON returns 400; a processing exception returns 500 so the provider can retry.',
            },
            {
                type: 'callout',
                tone: 'warning',
                title: 'A 401 means the secret or header does not match',
                text: 'You never paste a signature anywhere. Pixelforge computes it per delivery from your endpoint signing secret, and Lumora compares it with the same secret. If a delivery fails, open `storage/logs/laravel.log` and read the `Rejected Pixelforge webhook` entry: it lists the configuration, whether a secret is set, which signature headers arrived and every header name received. Then copy the endpoint signing secret from the Pixelforge dashboard into `PIXELFORGE_WEBHOOK_SECRET` and point `PIXELFORGE_SIGNATURE_HEADER` at the header listed there.',
            },
            {
                type: 'code',
                language: 'bash',
                code: `BODY='{"id":"evt_test_001","object":"event","type":"generation.succeeded","created_at":"2026-11-18T10:00:00Z","data":{"object":{"id":"gen_provider_id","object":"generation","status":"succeeded","image_url":"https://example.com/result.png","metadata":{"generation_uuid":"YOUR_LUMORA_GENERATION_UUID"},"usage":{"input_text_tokens":100,"input_image_tokens":0,"output_tokens":1100,"reasoning_tokens":200,"total_tokens":1400}}}}'
TS=$(date +%s)
SIG=$(printf '%s.%s' "$TS" "$BODY" | openssl dgst -sha256 -hmac "$PIXELFORGE_WEBHOOK_SECRET" -r | cut -d' ' -f1)

curl -i -X POST http://localhost:8000/webhooks/pixelforge \\
  -H "Content-Type: application/json" \\
  -H "Pixelforge-Signature: t=$TS,v1=$SIG" \\
  --data "$BODY"`,
            },
            {
                type: 'callout',
                tone: 'warning',
                title: 'Use real ids in the test body',
                text: 'To settle a real render, `data.object.id` must match the generation’s `provider_generation_id`, or `data.object.metadata.generation_uuid` must be the Lumora generation UUID. Unknown ids are stored as unmatched events and replayed later by the reconciliation command.',
            },
            {
                type: 'list',
                items: [
                    '**Deduplication** — the event id is taken from `id`, `event_id`, `data.event_id` or `event.id`, falling back to a SHA-256 hash of the raw body. A processed event returns `outcome: duplicate` without touching the wallet.',
                    '**Out-of-order events** — events older than the last applied provider transition are marked ignored. A success that is already settled cannot be settled again.',
                    '**Unmatched events** — delivery is stored with `generation_not_found`. `lumora:reconcile-usage` replays pending events once the generation exists.',
                    '**Exactly-once money** — the finalizer locks the generation row and writes a single settlement or refund; a unique `(generation_id, type)` index is the database backstop.',
                    '**Test keywords** — provider sandbox prompts `[fail]`, `[slow]` and `[duplicate]` are passed through unchanged. The suite proves failure refunds, non-terminal slow processing and duplicate-delivery idempotency.',
                ],
            },
        ],
    },
    {
        id: 'lifecycle',
        title: 'Generation lifecycle and pricing',
        intro: 'Every render follows the same path, whether the result arrives by webhook, polling or an immediate provider response.',
        blocks: [
            {
                type: 'steps',
                items: [
                    { title: 'Validate', text: '`StoreGenerationRequest` trims the prompt, validates length, size, quality, the per-render Remove provider caption choice and up to two `http(s)` reference URLs.' },
                    { title: 'Estimate and hold', text: '`GenerationService` calculates the provider rate plus markup, checks the balance and active-render limit, then writes a `hold` ledger row and a pending generation inside a transaction.' },
                    { title: 'Submit', text: '`SubmitGenerationJob` applies a provider rate limit, calls the create endpoint with an idempotency key and records the provider id. Client errors fail the job immediately and refund it; transient errors retry with backoff. A `404` on create is treated as transient too, because the provider also returns `not_found` while a resource propagates, and the idempotency key makes the retry safe. When the retries are exhausted the generation is failed and refunded.' },
                    { title: 'Process', text: 'The provider renders asynchronously. The UI polls the status endpoint while the scheduler queues fallback polls for in-progress generations.' },
                    { title: 'Settle or fail', text: 'A webhook, a poll or a synchronous response calls `GenerationFinalizer`. Success computes actual provider cost, applies markup, settles the difference and stores the image. The provider URL is signed and expires after 24 hours, so `MirrorGenerationImage` copies the PNG onto our disk and keeps the original URL in `meta.provider_image_url`; if the render was submitted with Remove provider caption checked, the caption is painted out during that copy. Failure refunds the full hold and records a zero customer charge.' },
                    { title: 'Expire stragglers', text: '`lumora:expire-generations` fails and refunds anything older than the configured stale window, so credits cannot be stuck forever.' },
                ],
            },
            {
                type: 'table',
                head: ['Status', 'Meaning', 'Wallet effect'],
                rows: [
                    ['`pending`', 'Created and waiting for a worker or provider acknowledgement.', 'Estimated charge held.'],
                    ['`processing`', 'Provider has the job and is rendering.', 'Estimated charge held.'],
                    ['`succeeded`', 'Image is available and the final cost is known.', 'Hold replaced by the actual charge; difference refunded or debited.'],
                    ['`failed`', 'Provider rejected, errored, timed out or never answered.', 'Full hold refunded; customer charge is zero.'],
                ],
            },
            {
                type: 'paragraph',
                text: 'Actual provider cost is resolved in this order: an explicit cost reported by the provider (`usage.cost_usd` and variants); token usage priced with the `rates` returned alongside the generation, then the configured per-million rates; then the documented token estimate. Estimated cost uses the same token model with the maximum reasoning budget, so the hold always covers the final charge. The customer charge is provider cost plus the configured markup, rounded up.',
            },
            {
                type: 'callout',
                tone: 'tip',
                title: 'Retries are safe',
                text: 'Submit retries reuse the same idempotency key and the same generation row. Provider retries, duplicate webhooks, polls and reconciliation all converge on the same idempotent finalizer, so a render cannot be charged or refunded twice.',
            },
        ],
    },
    {
        id: 'money',
        title: 'Money and the wallet',
        intro: 'All money is stored as integer micro-dollars. There are no floats anywhere in the wallet path.',
        blocks: [
            {
                type: 'list',
                items: [
                    '**1 USD = 1,000,000 micros.** One credit is pegged at 10,000 micros, or US$0.01.',
                    '`users.credit_balance_micros` is the spendable balance.',
                    '`credit_transactions` is an append-only ledger. Each row stores `amount_micros`, the resulting `balance_after_micros`, a type, a description and optional metadata.',
                    '`brick/math` handles parsing, division and formatting with explicit rounding.',
                    'Generation rows store estimated provider cost, estimated customer charge, actual provider cost, actual customer charge and the markup basis points used.',
                ],
            },
            {
                type: 'table',
                head: ['Ledger type', 'When it is written'],
                rows: [
                    ['`purchase`', 'A credit pack is bought. Idempotent through a unique reference.'],
                    ['`bonus`', 'Welcome credits or another grant.'],
                    ['`hold`', 'A render starts; the estimated charge is reserved.'],
                    ['`settlement`', 'A render succeeds; the difference between hold and actual charge is applied, including a zero row when they match.'],
                    ['`refund`', 'A render fails or expires; the full hold is returned.'],
                    ['`adjustment`', 'Manual correction.'],
                ],
            },
            {
                type: 'paragraph',
                text: 'A unique `(generation_id, type)` index plus `lockForUpdate()` inside a transaction means a generation can only ever have one hold, one settlement and one refund. Deposits are idempotent through their `reference` column, which is why a repeated purchase token credits the wallet exactly once.',
            },
            {
                type: 'callout',
                tone: 'info',
                title: 'Failed renders are free',
                text: 'When the provider reports usage, the finalizer records provider cost for admin reconciliation, but the customer charge is forced to zero and the full hold is refunded. Admins can see the provider-side loss without charging the user.',
            },
        ],
    },
    {
        id: 'key-classes',
        title: 'Key classes and what they do',
        intro: 'These are the files to read first when changing behaviour. Most product rules are centralised so that webhooks, polling and admin actions cannot diverge.',
        blocks: [
            {
                type: 'table',
                head: ['Class', 'Responsibility'],
                rows: [
                    ['`App\\Support\\Money`', 'Micro-dollar parsing, formatting, credit conversion and rounding.'],
                    ['`App\\Support\\Seo`', 'Per-path title, description, canonical, robots and JSON-LD metadata.'],
                    ['`Pixelforge\\PixelforgeClient`', 'Bearer-authenticated HTTP calls, timeouts, error mapping and structured logging.'],
                    ['`Pixelforge\\PricingCalculator`', 'Rate-card estimates, token usage extraction, actual cost resolution and markup.'],
                    ['`Pixelforge\\WebhookSignature`', 'Timestamped and raw HMAC verification with replay tolerance.'],
                    ['`Pixelforge\\UsageReconciler`', 'Pulls provider usage for a window, upserts usage records and supports admin reconciliation.'],
                    ['`Billing\\CreditService`', 'The only wallet writer: holds, settlement adjustments, refunds, purchases and grants.'],
                    ['`Generations\\GenerationService`', 'Creation rules, active-render limit, balance hold, dispatch, expiry and status payloads.'],
                    ['`Generations\\GenerationFinalizer`', 'Idempotent terminal state plus wallet settlement for success and failure.'],
                    ['`Generations\\WebhookProcessor`', 'Event identity, dedupe, out-of-order protection, unmatched storage and replay.'],
                    ['`Jobs\\SubmitGenerationJob`', 'Provider submission, rate limiting, retries and configuration failure handling.'],
                    ['`Jobs\\PollGenerationJob`', 'Status polling fallback, 404 tolerance and terminal handling.'],
                    ['`Admin\\AdminMetrics`', 'Revenue, provider cost, profit, daily series, success health and per-user spending.'],
                ],
            },
            {
                type: 'paragraph',
                text: 'Console commands are thin wrappers around services: `lumora:poll-generations` queues polls, `lumora:expire-generations` fails and refunds stale work, `lumora:reconcile-usage {--days=2}` syncs usage and replays unmatched webhooks, and `lumora:strip-image-captions` cleans renders mirrored before caption stripping existed while leaving renders whose studio choice was to keep the caption. The first three are scheduled in `routes/console.php`; the last is run on demand.',
            },
        ],
    },
    {
        id: 'frontend',
        title: 'Front-end structure and adding pages',
        intro: 'Pages live under `resources/js/features/<feature>/pages`. Shared UI, brand components and layouts stay outside features so they can be reused.',
        blocks: [
            {
                type: 'table',
                head: ['Area', 'Files'],
                rows: [
                    ['Pages', '`features/landing`, `auth`, `dashboard`, `generate`, `creations`, `billing`, `settings`, `admin`, `legal`, `docs`'],
                    ['Layouts', '`AppLayout.vue` for signed-in pages, `AuthLayout.vue` for authentication'],
                    ['UI kit', '`components/ui/` — Button, Card, Input, Textarea, Modal, Badge, ToastHost, Skeleton, Pagination and others'],
                    ['Brand', '`components/brand/` — Logo, ThemeToggle, StatusBadge, GenerationCard, CostBreakdown, SmartImage'],
                    ['Composables', 'Theme, toast, SEO, polling, sidebar, clipboard and click-outside'],
                    ['Helpers', '`lib/format.js`, `lib/routes.js`, `lib/pricing.js`, `lib/http.js`'],
                ],
            },
            {
                type: 'steps',
                items: [
                    { title: 'Create the controller', text: 'Run `php artisan make:controller ExampleController` and return `Inertia::render(\'features/example/pages/Example\', $props)`.' },
                    { title: 'Register the route', text: 'Add a named route in `routes/web.php`, inside the `auth` group if it needs a signed-in user.' },
                    { title: 'Create the page', text: 'Add `resources/js/features/example/pages/Example.vue`. Import `AppLayout` for app pages, call `useSeo()` and use the existing UI components.' },
                    { title: 'Add the URL helper', text: 'Put the path in `resources/js/lib/routes.js` instead of hardcoding it in components.' },
                    { title: 'Add SEO', text: 'Extend the match in `app/Support/Seo.php` with a title, description and indexable flag.' },
                    { title: 'Add navigation', text: 'Add the item to `AppLayout.vue` if the page belongs in the sidebar, including an icon and active matching key.' },
                    { title: 'Write a test', text: 'Use `php artisan make:test --phpunit ExampleTest` and assert the route, authorization and Inertia component.' },
                ],
            },
            {
                type: 'callout',
                tone: 'info',
                title: 'Pricing in the browser',
                text: '`resources/js/lib/pricing.js` mirrors the estimate maths for the live studio panel, but the server recalculates everything before money moves. Treat server values as authoritative and keep both formulas in sync if you change the markup model.',
            },
            {
                type: 'paragraph',
                text: 'Design tokens are defined in `resources/css/tokens.css`; small utilities such as `.ambient`, `.text-gradient`, `.surface` and `.tabular` live in `resources/css/utilities.css`. Prefer the semantic tokens (`bg-canvas`, `bg-surface`, `text-ink`, `text-subtle`, `border-line`) over raw colours so both themes stay consistent.',
            },
        ],
    },
    {
        id: 'admin',
        title: 'Admin dashboard and reconciliation',
        intro: 'Administrators get a separate dashboard at `/admin`, protected by the `admin` middleware and the `is_admin` flag on the user record.',
        blocks: [
            {
                type: 'list',
                items: [
                    'Revenue, provider cost and profit are calculated from settled generations in micro-dollars, then formatted for display.',
                    'Daily charts cover the selected window; success and failure health is split by status.',
                    'Top spenders are aggregated per user, including totals and render counts.',
                    '**Reconcile usage** calls `UsageReconciler`, which reads `GET /v1/usage` for the last two days, upserts `provider_usage_records` and reports the variance.',
                    'The same reconciliation runs hourly via `lumora:reconcile-usage` and also replays unmatched webhook events.',
                ],
            },
            {
                type: 'code',
                language: 'bash',
                code: `# Grant admin access to an existing account.
php artisan tinker --execute 'App\\Models\\User::where("email", "you@example.com")->update(["is_admin" => true]);'`,
            },
            {
                type: 'callout',
                tone: 'warning',
                title: 'Protect the admin role',
                text: 'Admin users can view every generation and provider cost. Grant the flag deliberately, audit who has it, and never expose admin routes without the `admin` middleware.',
            },
        ],
    },
    {
        id: 'testing',
        title: 'Tests and quality checks',
        intro: 'The suite runs on in-memory SQLite with the sync queue, so it needs no external services.',
        blocks: [
            {
                type: 'code',
                language: 'bash',
                code: `php artisan test                 # full suite
php artisan test --compact       # compact output
php artisan test --filter=WebhookTest
php artisan test tests/Feature/GenerationFlowTest.php

vendor/bin/pint                  # fix PHP style
vendor/bin/pint --test           # check only
npm run build                    # production front-end build`,
            },
            {
                type: 'table',
                head: ['Test file', 'Coverage'],
                rows: [
                    ['`tests/Unit/MoneyTest.php`', 'Micro-dollar maths, credit conversion, formatting and rounding.'],
                    ['`tests/Unit/WebhookSignatureTest.php`', 'Timestamped and raw signatures, malformed headers and tolerance.'],
                    ['`tests/Feature/GenerationFlowTest.php`', 'Validation, holds, success settlement, failure refunds, limits and expiry.'],
                    ['`tests/Feature/WebhookTest.php`', 'HMAC acceptance, duplicate delivery, out-of-order events, unmatched events and test keywords.'],
                    ['`tests/Feature/BillingTest.php`', 'Packages, idempotent purchases and wallet summaries.'],
                    ['`tests/Feature/AdminTest.php`', 'Metrics, authorization and reconciliation.'],
                    ['`tests/Feature/AuthorizationTest.php`', 'Ownership rules for viewing and deleting generations.'],
                    ['`tests/Feature/RegistrationTest.php`, `SettingsTest.php`', 'Welcome bonus, profile and password changes, account deletion.'],
                ],
            },
            {
                type: 'callout',
                tone: 'tip',
                title: 'Add tests with the change',
                text: 'Use factories and the existing states instead of hand-building models. Run the narrowest test file while iterating, then the full suite before finishing.',
            },
        ],
    },
    {
        id: 'deployment',
        title: 'Production deployment checklist',
        intro: 'Lumora needs a standard PHP host with a persistent database, a queue worker and a scheduler. Static assets are built with Vite; no Node server is required at runtime.',
        blocks: [
            {
                type: 'steps',
                items: [
                    { title: 'Provision the host', text: 'PHP 8.3+, a MySQL database, HTTPS, and writable `storage/` and `bootstrap/cache/`.' },
                    { title: 'Set production environment values', text: '`APP_ENV=production`, `APP_DEBUG=false`, the real `APP_URL`, a generated `APP_KEY`, database credentials, a real mailer, and Pixelforge credentials. Use your host’s secret manager rather than a committed file.' },
                    { title: 'Install and build', text: 'Run `composer install --no-dev --optimize-autoloader`, `npm ci`, `npm run build` and `php artisan migrate --force`.' },
                    { title: 'Link public storage', text: 'Run `php artisan storage:link`. Mirrored renders are written to `storage/app/public/generations` and served from `/storage/generations`.' },
                    { title: 'Cache for production', text: 'Run `php artisan config:cache`, `php artisan route:cache` and `php artisan view:cache`.' },
                    { title: 'Run the queue worker', text: '`php artisan queue:work --tries=5 --timeout=120`, supervised by your platform or Supervisor, and restart it on every deploy.' },
                    { title: 'Run the scheduler', text: 'Add the cron entry `* * * * * php /path/to/artisan schedule:run` or keep `php artisan schedule:work` running under supervision.' },
                    { title: 'Point Pixelforge at the domain', text: 'Register `https://your-domain.com/webhooks/pixelforge`, set the same signing secret, and send a test delivery.' },
                    { title: 'Submit to search engines', text: 'Confirm `/robots.txt` and `/sitemap.xml` resolve over HTTPS, then add the domain to Google Search Console and submit the sitemap. Private pages are disallowed and served with `noindex`.' },
                    { title: 'Smoke test', text: 'Register, confirm the welcome bonus, buy a package (demo checkout), run a render, verify settlement, open the admin dashboard and reconcile usage.' },
                ],
            },
            {
                type: 'callout',
                tone: 'warning',
                title: 'Payments',
                text: 'The included purchase flow is a demo checkout. Before accepting money, replace it with a real payment provider and verify the callback before granting credits.',
            },
        ],
    },
    {
        id: 'security',
        title: 'Security notes',
        intro: 'The main boundaries are provider secrets, webhook authenticity, user ownership and money integrity.',
        blocks: [
            {
                type: 'list',
                items: [
                    'API keys and webhook secrets are read from configuration on the server only. They are never included in Inertia props.',
                    'Webhooks are CSRF-exempt by design, but every request requires a valid HMAC signature and a fresh timestamp.',
                    'Generation viewing and deletion are enforced by `GenerationPolicy`; admin routes require the `admin` middleware.',
                    'Passwords use Laravel’s hashed cast. Changing a password requires the current password; account deletion also requires confirmation.',
                    'All money columns are integers. Wallet writes happen inside transactions with row locks and unique constraints.',
                    'Prompts, reference URLs and provider responses are stored for auditability. Do not log or accept secrets in prompts.',
                    'Use HTTPS everywhere, keep `APP_DEBUG=false` in production, and rotate provider credentials if they are exposed.',
                ],
            },
            {
                type: 'callout',
                tone: 'info',
                title: 'Reporting a problem',
                text: 'Email the support address configured in `LUMORA_SUPPORT_EMAIL`. Include the generation UUID and approximate timestamp, but never include API keys or webhook secrets.',
            },
        ],
    },
];
</script>

<template>
    <DocLayout
        eyebrow="Developer guide"
        title="Build, configure and extend Lumora"
        intro="How the code is arranged, how a render moves through the system, how money is kept exact, and how to connect real Pixelforge keys and webhooks for end-to-end testing."
        :sections="sections"
        updated="November 2026"
        kind="developer"
    />
</template>
